<?php
// Local diagnostic only. Report presence, never key values.
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo json_encode([
    'parent_environment_file' => $app->environmentFilePath(),
    'parent_key_configured' => is_string(config('app.key')) && config('app.key') !== '',
    'parent_ENV_APP_KEY_present' => array_key_exists('APP_KEY', $_ENV),
    'php_binary' => PHP_BINARY,
    'php_ini' => php_ini_loaded_file(),
    'variables_order' => ini_get('variables_order'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
$process = new Symfony\Component\Process\Process(
    [PHP_BINARY, '-S', '127.0.0.1:8001', $root.'/scripts/check-local-key.php'],
    $app->publicPath(),
    array_merge($_ENV, ['PHP_CLI_SERVER_WORKERS' => false])
);
$process->setTimeout(null);
exit($process->run(function ($type, $buffer) { echo $buffer; }));
