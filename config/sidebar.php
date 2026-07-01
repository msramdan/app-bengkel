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
