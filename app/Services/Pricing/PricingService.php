<?php

namespace App\Services\Pricing;

use Carbon\Carbon;
use App\Models\Pricing\VariantPrice;
use App\Models\Pricing\StoreVariantPrice;

class PricingService
{
    /**
     * Resolve selling price for a variant.
     *
     * @param int $companyId
     * @param int $variantId
     * @param string $channelCode  (POS, WEBSITE, B2B)
     * @param string $currencyCode (USD, GBP)
     * @param string|null $countryCode (US, UK)
     * @param int|null $storeId
     *
     * @return array
     * [
     *   price => decimal,
     *   currency => string,
     *   source => string (store|country|global)
     * ]
     */
    public function getPrice(
        int $companyId,
        int $variantId,
        string $channelCode,
        string $currencyCode,
        ?string $countryCode = null,
        ?int $storeId = null
    ): array {
        $today = Carbon::today();

        /**
         * Base query: valid variant prices
         */
        $baseQuery = VariantPrice::query()
            ->where('company_id', $companyId)
            ->where('product_variant_id', $variantId)
            ->whereHas(
                'priceChannel',
                fn($q) =>
                $q->where('code', $channelCode)->where('is_active', true)
            )
            ->whereHas(
                'currency',
                fn($q) =>
                $q->where('code', $currencyCode)->where('is_active', true)
            )
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $today);
            });

        /**
         * 1️⃣ STORE-SPECIFIC OVERRIDE
         */
        if ($storeId) {
            $storePrice = $baseQuery
                ->whereHas(
                    'storeOverrides',
                    fn($q) =>
                    $q->where('store_id', $storeId)
                )
                ->with([
                    'storeOverrides' => fn($q) =>
                    $q->where('store_id', $storeId)
                ])
                ->orderByDesc('valid_from')
                ->first();

            if ($storePrice) {
                return [
                    'price' => $storePrice->storeOverrides->first()->price_override,
                    'currency' => $currencyCode,
                    'source' => 'store',
                ];
            }
        }

        /**
         * 2️⃣ COUNTRY-SPECIFIC PRICE
         */
        if ($countryCode) {
            $countryPrice = (clone $baseQuery)
                ->where('country_code', $countryCode)
                ->orderByDesc('valid_from')
                ->first();

            if ($countryPrice) {
                return [
                    'price' => $countryPrice->price,
                    'currency' => $currencyCode,
                    'source' => 'country',
                ];
            }
        }

        /**
         * 3️⃣ GLOBAL PRICE (country = NULL)
         */
        $globalPrice = (clone $baseQuery)
            ->whereNull('country_code')
            ->orderByDesc('valid_from')
            ->first();

        if ($globalPrice) {
            return [
                'price' => $globalPrice->price,
                'currency' => $currencyCode,
                'source' => 'global',
            ];
        }

        /**
         * ❌ NO PRICE FOUND
         */
        throw new \RuntimeException('No valid price configured for this product variant.');
    }
}
