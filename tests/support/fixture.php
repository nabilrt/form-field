<?php

require __DIR__ . '/app/bootstrap.php';

q('INSERT INTO admins(name,email,password) VALUES(?,?,?)', [
    'Owner',
    'admin@test.local',
    password_hash('MerchantPassword123', PASSWORD_DEFAULT),
]);

$settings = [
    'store_name' => 'Form & Field',
    'store_email' => 'hello@test.local',
    'store_phone' => '+8801700000000',
    'store_address' => 'Dhaka, Bangladesh',
    'shipping' => '120',
    'free_shipping' => '5000',
    'tax_rate' => '0',
    'bank_details' => 'Example bank instructions for integration testing',
    'announcement' => 'Good things for your everyday.',
];

foreach ($settings as $key => $value) {
    q('INSERT INTO settings(`key`,value) VALUES(?,?)', [$key, $value]);
}

require __DIR__ . '/scripts/seed.php';
