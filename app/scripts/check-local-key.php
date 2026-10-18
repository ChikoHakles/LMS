<?php
// Report key status only; never print key or other environment values.
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$key = config('app.key');
$decoded = is_string($key) && str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
echo json_encode([
    'php_version' => PHP_VERSION,
    'app_path' => $app->basePath(),
    'environment_file' => $app->environmentFilePath(),
    'environment_file_exists' => is_file($app->environmentFilePath()),
    'config_cached' => $app->configurationIsCached(),
    'process_APP_KEY_present' => getenv('APP_KEY') !== false,
    'key_configured' => is_string($key) && $key !== '',
    'key_valid_for_AES_256_CBC' => is_string($decoded) && strlen($decoded) === 32,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
