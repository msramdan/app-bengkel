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
];
