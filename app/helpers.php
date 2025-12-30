<?php

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        // static $settings = null;

        // if ($settings === null) {
        //     $settings = cache()->rememberForever('app_settings', function () {
        //         return \App\Models\Setting::pluck('value', 'key')->toArray();
        //     });
        // }

        // $value = $settings[$key] ?? $default;

        // if (is_string($value) && Str::startsWith($value, ['settings/'])) {
        //     return 'storage/' . ltrim($value, '/');
        // }

        // return $value;
    }
}
