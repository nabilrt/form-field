<?php

$testInput = json_decode(file_get_contents($argv[1]), true);
$_SERVER['REQUEST_METHOD'] = $testInput['method'];
$_SERVER['REQUEST_URI'] = $testInput['uri'];
$_SERVER['REMOTE_ADDR'] = $testInput['ip'] ?? '127.0.0.1';
$_SERVER['HTTP_REFERER'] = $testInput['referer'] ?? '/admin';
$_SERVER['HTTP_ACCEPT'] = $testInput['accept'] ?? '';
$_POST = $testInput['post'] ?? [];
$_GET = $testInput['query'] ?? [];
$_FILES = $testInput['files'] ?? [];

register_shutdown_function(function () {
    file_put_contents(__DIR__ . '/storage/state.json', json_encode([
        'session' => $_SESSION ?? [],
        'status' => http_response_code() ?: 200,
        'queries' => $GLOBALS['testQueryCount'] ?? 0,
    ]));
});

require __DIR__ . '/public/index.php';
