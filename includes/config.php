<?php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'cecoeorg_monitoring',
        'user' => 'cecoeorg_firaol927',
        'pass' => '1%80^C^YyPU3_%Md',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'theme_color' => '#025529',
        'monitor_prefix' => 'CCSPM',
        'monitor_pad' => 2,
    ],
    'mail' => [
        // SMTP settings (Gmail SMTP with app password)
        'smtp_host'   => 'mail.cecoe.org',
'smtp_user'   => 'firaol@cecoe.org',
'smtp_pass'   => '1%80^C^YyPU3_%Md', 
'smtp_port'   => 465,
'smtp_secure' => 'ssl',
'from'        => 'firaol@cecoe.org',
'from_name'   => 'CCSPS Tracking',
    ],
    // bcrypt cost
    'security' => [
        'bcrypt_cost' => 12,
    ],
];