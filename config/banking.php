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

    'payments' => [
        'default_gateway' => env('PAYMENTS_DEFAULT_GATEWAY', 'card'), // card | wire | legacy
    ],

    'base_rates' => [
        'savings'    => 0.030,  // 3%
        'checking'   => 0.005,  // 0.5%
        'investment' => 0.060,  // 6%
        'loan'       => 0.080,  // 8% (for demo)
    ],

    /**
     * Market condition multiplier to simulate "market conditions"
     */
    'markets' => [
        'normal'         => ['multiplier' => 1.00],
        'high_inflation' => ['multiplier' => 1.20],
        'low_rates'      => ['multiplier' => 0.80],
        'tight'          => ['multiplier' => 1.10],
    ],

    /**
     * Safety: min interest amount (avoid micro noise)
     */
    'min_interest' => 0.01,

    /**
     * Default market if none provided
     */
    'default_market' => 'normal',
];
