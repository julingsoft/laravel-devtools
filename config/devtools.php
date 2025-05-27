<?php

return [
    'dist' => storage_path('code'),
    'ignore_tables' => [],
    'ignore_controllers' => [],
    'ignore_singular' => true,
    'multi_module' => false,
    'multi_language' => [
        'Gin' => [],
        'Laravel' => [],
        'ThinkPHP' => [],
        'Spring' => [
            'package_name' => 'com.xxx.xxx',
        ],
    ],
];
