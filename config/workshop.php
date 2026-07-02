<?php

return [
    /*
    | Komisi teknisi dihitung dari total biaya jasa (bukan sparepart).
    | Sparepart 100% untuk toko.
    */
    'commission' => [
        'technician_percent' => 80,
        'owner_percent' => 20,
    ],

    /** Default komisi teknisi dari total jasa (%) — bisa diubah per teknisi di master data. */
    'default_technician_commission_percent' => 20,

    /** Label pelanggan lewat tanpa data master (walk-in). */
    'walk_in_customer_label' => 'Umum',

    'payment_methods' => [
        'cash' => 'Cash',
        'qris' => 'QRIS',
        'transfer' => 'Transfer Bank',
    ],

    /** Pembelian: hanya cash atau transfer ke akun bank. */
    'purchase_payment_methods' => [
        'cash' => 'Cash',
        'transfer' => 'Transfer Bank',
    ],
];
