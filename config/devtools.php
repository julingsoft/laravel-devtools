<?php

return [
    'dist' => storage_path('code'),
    'multi_module' => true,
    'ignore_tables' => [
        'cache',
        'cache_locks',
        'failed_jobs',
        'jobs',
        'job_batches',
        'migrations',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
    ],
    'ignore_columns' => [
        'created_at',
        'updated_at',
        'deleted_at',
        'user' => [
            'xxxx',
        ]
    ],
    'ignore_controllers' => [
        'base',
    ],
    'ignore_singular' => true,
];
