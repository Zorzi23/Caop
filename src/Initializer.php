<?php
declare(strict_types=1);

namespace CaOp;

use PhpParser\Node\Expr\List_;
use Symfony\Component\Yaml\Exception\ParseException;

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
        $oTelemetry = EntityTelemetry::createWithDefaults(
            $oConfig->getName(),
            $oLoader->getInstrumentationConfig()
        );
        $oLoader->registerAllEntities($oTelemetry);
        return true;
    } catch (ParseException $oException) {
        error_log("Telemetry initialization failed: Failed to parse YAML configuration: {$oException->getMessage()}");
        return false;
    } catch (\Exception $oException) {
        error_log("Telemetry initialization failed: {$oException->getMessage()}");
        return false;
    }
}

$aConfigs = array_filter([
    getenv('CAOP_TELEMETRY_CONFIG_PATH'),
    $_ENV['CAOP_TELEMETRY_CONFIG_PATH'] ?? null,
    getcwd() . '/telemetry_config.yml'
]);

list($sConfigPath) = array_values($aConfigs);

initializeTelemetry($sConfigPath);