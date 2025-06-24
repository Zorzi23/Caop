<?php
declare(strict_types=1);
use CaOp\EntityTelemetry;
use CaOp\TelemetryConfigLoader;
use Symfony\Component\Yaml\Exception\ParseException;
require_once(dirname(__DIR__).'/vendor/autoload.php');

/**
 * Initializes the telemetry system with the specified YAML configuration
 *
 * @param string $sConfigPath Path to the YAML configuration file
 * @return bool True if initialization is successful, false otherwise
 */
function initializeTelemetry(string $sConfigPath): bool
{
    if (!file_exists($sConfigPath)) {
        error_log("Telemetry initialization failed: Configuration file not found: {$sConfigPath}");
        return false;
    }

    try {
        $oLoader = TelemetryConfigLoader::createFromYaml($sConfigPath);
        $oConfig = $oLoader->toGenericObject();
        $oTelemetry = EntityTelemetry::createWithDefaults($oConfig->getName());
        $oLoader->registerAllEntities($oTelemetry);
        return true;
    } catch (ParseException $oException) {
        error_log("Telemetry initialization failed: Failed to parse YAML configuration: {$oException->getMessage()}");
        return false;
    } catch (Exception $oException) {
        error_log("Telemetry initialization failed: {$oException->getMessage()}");
        return false;
    }
}

// Initialize telemetry with the configuration file
$sConfigPath = __DIR__ . '/telemetry_config.yml';
initializeTelemetry($sConfigPath);

// Test function for monitoring
function teste(): void
{
    print_r('dadwad');
}

// Execute test function
teste();