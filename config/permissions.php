<?php

return [
    [
        'group' => 'Dashboard',
        'access' => ['dashboard view'],
    ],
    [
        'group' => 'User',
        'access' => ['user view', 'user create', 'user edit', 'user delete'],
    ],
    [
        'group' => 'Role',
        'access' => ['role view', 'role create', 'role edit', 'role delete'],
    ],
    [
        'group' => 'Pelanggan',
        'access' => ['customer view', 'customer create', 'customer edit', 'customer delete'],
    ],
    [
        'group' => 'Teknisi',
        'access' => ['technician view', 'technician create', 'technician edit', 'technician delete'],
    ],
    [
        'group' => 'Kategori Barang',
        'access' => ['item category view', 'item category create', 'item category edit', 'item category delete'],
    ],
    [
        'group' => 'Satuan Barang',
        'access' => ['item unit view', 'item unit create', 'item unit edit', 'item unit delete'],
    ],
    [
        'group' => 'Barang / Inventory',
        'access' => ['item view', 'item create', 'item edit', 'item delete'],
    ],
    [
        'group' => 'Stok Masuk',
        'access' => ['stock in view', 'stock in create'],
    ],
    [
        'group' => 'Stok Keluar',
        'access' => ['stock out view', 'stock out create'],
    ],
    [
        'group' => 'Laporan Stok',
        'access' => ['stock report view'],
    ],
    [
        'group' => 'Master Jasa',
        'access' => ['workshop service view', 'workshop service create', 'workshop service edit', 'workshop service delete'],
    ],
    [
        'group' => 'Transaksi',
        'access' => ['transaction view', 'transaction create', 'transaction edit', 'transaction delete'],
    ],
    [
        'group' => 'Pembelian',
        'access' => ['purchase view', 'purchase create'],
    ],
    [
        'group' => 'Laporan Keuangan',
        'access' => ['financial report view'],
    ],
    [
        'group' => 'Pemasukan Manual',
        'access' => ['manual income view', 'manual income create', 'manual income cancel'],
    ],
    [
        'group' => 'Pengeluaran Manual',
        'access' => ['manual expense view', 'manual expense create', 'manual expense cancel'],
    ],
    [
        'group' => 'Akun Bank',
        'access' => ['bank account view', 'bank account create', 'bank account edit', 'bank account delete'],
    ],
    [
        'group' => 'Pengaturan Aplikasi',
        'access' => ['settings view', 'settings edit'],
    ],
];
