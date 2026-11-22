<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
$database = envv('DB_DATABASE', 'form_field');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
    exit("Invalid database name\n");
}

$socket = envv('DB_SOCKET');
$dsn = $socket ? 'mysql:unix_socket=' . $socket : 'mysql:host=' . envv('DB_HOST', '127.0.0.1') . ';port=' . envv('DB_PORT', '3306');
try {
    $server = new PDO($dsn, envv('DB_USERNAME', 'root'), envv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $server->exec("USE `$database`");
    foreach (explode(';', file_get_contents(__DIR__ . '/../database/schema.sql')) as $sql) {
        if (trim($sql)) {
            $server->exec($sql);
        }
    }

    if (!q('SELECT COUNT(*) FROM admins')->fetchColumn()) {
        $email = getenv('ADMIN_EMAIL') ?: 'admin@formandfield.test';
        $password = getenv('ADMIN_PASSWORD') ?: bin2hex(random_bytes(9));
        if (strlen($password) < 12) {
            exit("ADMIN_PASSWORD must be at least 12 characters.\n");
        }
        q(
            'INSERT INTO admins(name,email,password) VALUES(?,?,?)',
            ['Store owner', $email, password_hash($password, PASSWORD_DEFAULT)]
        );
        echo "Admin account: $email\nPassword: $password\nSave this password; it is shown only once.\n";
    }

    $settings = [
        'store_name' => 'Form & Field',
        'store_email' => 'hello@formandfield.test',
        'store_phone' => '+880 1700 000000',
        'store_address' => 'Dhaka, Bangladesh',
        'shipping' => '120',
        'free_shipping' => '5000',
        'tax_rate' => '0',
        'bank_details' => '',
        'announcement' => 'A little more room for living. Free delivery on orders over ৳5,000.',
    ];
    foreach ($settings as $k => $v) {
        q('INSERT IGNORE INTO settings(`key`,value) VALUES(?,?)', [$k, $v]);
    }

    if (!q('SELECT COUNT(*) FROM products')->fetchColumn()) {
        require __DIR__ . '/seed.php';
    }

    echo "Store installed. Run: php -S 127.0.0.1:8080 -t public router.php\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Installation failed: " . $e->getMessage() . "\n");
    exit(1);
}
