<?php

return [
    'reports' => [
        'storage_path' => storage_path('app/reports'),
        'default_format' => 'pdf',
        'keep_days' => 30,
    ],
    
    'pdf' => [
        'paper' => 'a4',
        'orientation' => 'portrait',
        'font' => 'dejavu sans',
    ],
    
    'excel' => [
        'auto_size' => true,
        'pre_calculate_formulas' => false,
    ],
];