<?php

return [
    'name' => 'Restore',
    'permissions' => ['restore.view', 'restore.verify', 'restore.execute'],
    'default_role_permissions' => [
        'admin' => ['restore.view', 'restore.verify', 'restore.execute'],
        'manager' => ['restore.view'],
        'bendahara' => ['restore.view'],
        'petugas' => ['restore.view'],
        'auditor' => ['restore.view'],
    ],
];
