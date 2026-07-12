<?php

return [
    'defaults' => [
        'app_name' => env('APP_BRAND_NAME', 'Atha Motor'),
        'app_tagline' => env('APP_BRAND_TAGLINE', 'Sistem Manajemen Bengkel'),
        'app_description' => 'Sistem manajemen bengkel untuk transaksi, stok, dan layanan pelanggan.',
        'company_address' => '',
        'company_whatsapp' => '',
        'company_logo' => '',
        'hiwa_enabled' => false,
        'hiwa_token_device' => env('HIWA_TOKEN_DEVICE', ''),
        'oil_change_reminder_enabled' => false,
        'oil_change_reminder_months' => 3,
        'oil_change_workshop_service_ids' => [],
    ],

    'encrypted_keys' => [
        'hiwa_token_device',
    ],

    'json_array_keys' => [
        'oil_change_workshop_service_ids',
    ],
];
