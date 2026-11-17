<?php

return [
    'currency' => 'USD',
    'origin' => [
        'name' => 'Seoul, South Korea',
        'latitude' => 37.5665,
        'longitude' => 126.9780,
    ],
    'rates' => [
        'base_fee_usd' => env('SHIPPING_BASE_FEE_USD'),
        'fee_per_km_usd' => env('SHIPPING_FEE_PER_KM_USD'),
    ],
    'dataset_path' => base_path('database/data/shipping'),
];
