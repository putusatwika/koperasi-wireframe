<?php

return [
    'name' => 'Notifikasi',
    'permissions' => ['notification.view', 'notification.manage_template', 'notification.send', 'notification.retry'],
    'default_role_permissions' => [
        'admin' => ['notification.view', 'notification.manage_template', 'notification.send', 'notification.retry'],
        'manager' => ['notification.view'],
        'bendahara' => ['notification.view'],
        'petugas' => ['notification.view'],
        'auditor' => ['notification.view'],
    ],
];
