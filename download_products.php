<?php

$baseUrl = "https://vapeshopdistro.co.uk/products.json?limit=250";
$page = 1;
$allProducts = [];

echo "Starting download...\n";

while (true) {
    $url = $baseUrl . "&page=" . $page;
    echo "Fetching page $page... ";
    
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    
    $json = @file_get_contents($url, false, $context);
    
    if ($json === false) {
        echo "Failed to fetch page $page. Stopping.\n";
        break;
    }
    
    $data = json_decode($json, true);
    
    if (empty($data['products'])) {
        echo "No more products found. Finished.\n";
        break;
    }
    
    $count = count($data['products']);
    echo "Found $count products.\n";
    
    $allProducts = array_merge($allProducts, $data['products']);
    
    $page++;
    // Polite delay
    sleep(1);
}

$output = ['products' => $allProducts];
file_put_contents(__DIR__ . '/database/seeders/data/products_full.json', json_encode($output, JSON_PRETTY_PRINT));

echo "Total products saved: " . count($allProducts) . "\n";
echo "Saved to database/seeders/data/products_full.json\n";
