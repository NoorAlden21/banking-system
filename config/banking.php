<?php

return [
    'currency' => env('BANKING_CURRENCY', 'USD'),

    'approvals' => [
        'manager_threshold' => env('BANKING_MANAGER_THRESHOLD', '10000.00'),
    ],

    'alerts' => [
        'large_transaction_threshold' => env('BANKING_LARGE_TX_THRESHOLD', '20000.00'),
        'risk_roles' => ['admin', 'manager'],
    ],

    'aml' => [
        'enabled' => true,
        'block_threshold' => env('BANKING_AML_BLOCK_THRESHOLD', '99999999.00'),
    ],
];
