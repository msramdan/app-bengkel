<?php

return [
    'menus' => [
        [
            'title' => 'Menu Utama',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'bi-speedometer',
                    'route' => 'dashboard',
                    'permission' => 'dashboard view',
                ],
            ],
        ],
        [
            'title' => 'Master Data',
            'items' => [
                [
                    'label' => 'Pelanggan',
                    'icon' => 'bi-person-vcard',
                    'route' => 'customers.index',
                    'permission' => 'customer view',
                ],
                [
                    'label' => 'Supplier',
                    'icon' => 'bi-truck',
                    'route' => 'suppliers.index',
                    'permission' => 'supplier view',
                ],
                [
                    'label' => 'Teknisi',
                    'icon' => 'bi-wrench-adjustable',
                    'route' => 'technicians.index',
                    'permission' => 'technician view',
                ],
            ],
        ],
        [
            'title' => 'Inventory',
            'items' => [
                [
                    'label' => 'Inventory',
                    'icon' => 'bi-box-seam',
                    'permissions' => [
                        'item view', 'item category view', 'item unit view',
                        'stock in view', 'stock out view', 'stock report view',
                    ],
                    'submenus' => [
                        [
                            'label' => 'Data Barang',
                            'route' => 'items.index',
                            'permission' => 'item view',
                        ],
                        [
                            'label' => 'Kategori Barang',
                            'route' => 'item-categories.index',
                            'permission' => 'item category view',
                        ],
                        [
                            'label' => 'Satuan Barang',
                            'route' => 'item-units.index',
                            'permission' => 'item unit view',
                        ],
                        [
                            'label' => 'Stok Masuk',
                            'route' => 'stock-ins.index',
                            'permission' => 'stock in view',
                        ],
                        [
                            'label' => 'Stok Keluar',
                            'route' => 'stock-outs.index',
                            'permission' => 'stock out view',
                        ],
                        [
                            'label' => 'Laporan Stok',
                            'route' => 'stock-reports.index',
                            'permission' => 'stock report view',
                        ],
                    ],
                ],
                [
                    'label' => 'Master Jasa',
                    'icon' => 'bi-tools',
                    'route' => 'workshop-services.index',
                    'permission' => 'workshop service view',
                ],
            ],
        ],
        [
            'title' => 'Transaksi',
            'items' => [
                [
                    'label' => 'Transaksi',
                    'icon' => 'bi-receipt-cutoff',
                    'permissions' => ['transaction view', 'purchase view'],
                    'submenus' => [
                        [
                            'label' => 'Transaksi Penjualan',
                            'route' => 'transactions.index',
                            'permission' => 'transaction view',
                        ],
                        [
                            'label' => 'Pembelian Barang',
                            'route' => 'purchases.index',
                            'permission' => 'purchase view',
                        ],
                    ],
                ],
                [
                    'label' => 'Keuangan',
                    'icon' => 'bi-wallet2',
                    'permissions' => ['financial report view', 'manual income view', 'manual expense view'],
                    'submenus' => [
                        [
                            'label' => 'Laporan Keuangan',
                            'route' => 'financial-reports.index',
                            'permission' => 'financial report view',
                        ],
                        [
                            'label' => 'Pemasukan Manual',
                            'route' => 'manual-incomes.index',
                            'permission' => 'manual income view',
                        ],
                        [
                            'label' => 'Pengeluaran Manual',
                            'route' => 'manual-expenses.index',
                            'permission' => 'manual expense view',
                        ],
                    ],
                ],
                [
                    'label' => 'Akun Bank',
                    'icon' => 'bi-bank',
                    'route' => 'bank-accounts.index',
                    'permission' => 'bank account view',
                ],
            ],
        ],
        [
            'title' => 'Pengaturan',
            'items' => [
                [
                    'label' => 'Pengaturan Aplikasi',
                    'icon' => 'bi-gear',
                    'route' => 'settings.edit',
                    'permission' => 'settings view',
                ],
            ],
        ],
        [
            'title' => 'Pengguna',
            'items' => [
                [
                    'label' => 'Users & Roles',
                    'icon' => 'bi-people',
                    'permissions' => ['user view', 'role view'],
                    'submenus' => [
                        [
                            'label' => 'Data User',
                            'route' => 'users.index',
                            'permission' => 'user view',
                        ],
                        [
                            'label' => 'Role & Permission',
                            'route' => 'roles.index',
                            'permission' => 'role view',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
