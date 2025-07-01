<?php
declare(strict_types=1);
$sTelemetryConfigPath = __DIR__.'/configs/telemetry_config.yml';
putenv("CAOP_TELEMETRY_CONFIG_PATH={$sTelemetryConfigPath}");
require_once(dirname(__DIR__).'/vendor/autoload.php');
use CaOp\Template\TelemetryTemplates;
TelemetryTemplates::singleton()->handleRequest();

$conn = pg_connect("host=postgres dbname=testdb user=testuser password=12345");

$xResult = pg_query($conn, 'SELECT * FROM products');

$xReturn = pg_fetch_object($xResult);

print_r(
    $xReturn
);

$ch = curl_init('http://httpbin.org/get');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$xReturn = curl_exec($ch);

print_r(
    $xReturn
);