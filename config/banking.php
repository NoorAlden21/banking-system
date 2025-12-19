<?php

return [
    'currency' => env('BANKING_CURRENCY', 'USD'),

    'approvals' => [
        // أي تحويل أكبر من هذا يحتاج موافقة (نقدر نعطّله بوضعه رقم كبير)
        'manager_threshold' => env('BANKING_MANAGER_APPROVAL_THRESHOLD', '10000.00'),
    ],
];
