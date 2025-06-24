<?php
declare(strict_types=1);

namespace CaOp;

use ObjectFlow\GenericObject;
use ObjectFlow\Trait\InstanceTrait;
use Symfony\Component\Yaml\Yaml;
use CaOp\MonitoredEntity\MonitoredEntityBuilder;

/**
 * Loads and manages telemetry configuration from YAML files
 */
final class TelemetryConfigLoader
{
    use InstanceTrait;

    /** @var array<string, mixed> */
    private array $aConfig;

    /**
     * Private constructor to enforce factory method usage
     *
     * @param array<string, mixed> $aConfig Telemetry configuration
     */
    private function __construct(array $aConfig)
    {
        $this->aConfig = $aConfig;
    }

    /**
     * Creates an instance from a YAML configuration file
     *
     * @param string $sPath Path to the YAML configuration file
     * @return self
     */
    public static function createFromYaml(string $sPath): self
    {
        if (!file_exists($sPath)) {
            error_log("TelemetryConfigLoader: Configuration file not found: {$sPath}");
            return new self([]);
        }

        try {
            return new self(Yaml::parseFile($sPath));
        } catch (\Symfony\Component\Yaml\Exception\ParseException $oException) {
            error_log("TelemetryConfigLoader: Failed to parse YAML configuration: {$oException->getMessage()}");
            return new self([]);
        }
    }

    /**
     * Creates an instance from an array configuration
     *
     * @param array<string, mixed> $aConfig Configuration array
     * @return self
     */
    public static function createFromArray(array $aConfig): self
    {
        return new self($aConfig);
    }

    /**
     * Retrieves the loaded configuration
     *
     * @return array<string, mixed> Configuration array
     */
    public function getConfig(): array
    {
        return $this->aConfig;
    }

    /**
     * Converts configuration to a GenericObject
     *
     * @return GenericObject Configuration as a generic object
     */
    public function toGenericObject(): GenericObject
    {
        return GenericObject::fromArrayObject($this->aConfig);
    }

    /**
     * Registers all configured entities with the telemetry system
     *
     * @param EntityTelemetry $oTelemetry Telemetry system instance
     */
    public function registerAllEntities(EntityTelemetry $oTelemetry): void
    {
        $oBuilder = MonitoredEntityBuilder::create();

        // Register functions
        foreach ($this->aConfig['entities']['functions'] ?? [] as $aFnConfig) {
            if (!isset($aFnConfig['name'])) {
                error_log("TelemetryConfigLoader: Skipping function with missing name");
                continue;
            }
            $oEntity = $oBuilder->forFunction($aFnConfig['name']);
            $aHookConfig = $this->createHookConfig($aFnConfig);
            $oTelemetry->registerMonitoredEntity($oEntity, $aHookConfig, $aFnConfig['parent'] ?? null);
        }

        // Register methods
        foreach ($this->aConfig['entities']['methods'] ?? [] as $aMethodConfig) {
            if (!isset($aMethodConfig['class'], $aMethodConfig['method'])) {
                error_log("TelemetryConfigLoader: Skipping method with missing class or method");
                continue;
            }
            $oEntity = $oBuilder->forMethod($aMethodConfig['class'], $aMethodConfig['method']);
            $aHookConfig = $this->createHookConfig($aMethodConfig);
            $oTelemetry->registerMonitoredEntity($oEntity, $aHookConfig, $aMethodConfig['parent'] ?? null);
        }

        // Register groups
        foreach ($this->aConfig['entities']['groups'] ?? [] as $sGroupName => $aGroupConfig) {
            $aCommonAttributes = $aGroupConfig['common_attributes'] ?? [];

            foreach ($aGroupConfig['items'] ?? [] as $aItem) {
                if (!isset($aItem['class'], $aItem['method'])) {
                    error_log("TelemetryConfigLoader: Skipping group item with missing class or method in group {$sGroupName}");
                    continue;
                }
                $oEntity = $oBuilder->forMethod(
                    $aItem['class'],
                    $aItem['method'],
                    $aItem['static'] ?? false
                );
                $aConfig = $this->createHookConfig($aItem);
                $aConfig['attributes'] = array_merge($aConfig['attributes'], $aCommonAttributes);
                $oTelemetry->registerMonitoredEntity($oEntity, $aConfig, $aItem['parent'] ?? null);
            }
        }
    }

    /**
     * Creates hook configuration from provided config array
     *
     * @param array<string, mixed> $aConfig Configuration data
     * @return array<string, mixed> Hook configuration
     */
    private function createHookConfig(array $aConfig): array
    {
        return [
            'span_name' => $aConfig['span_name'] ?? null,
            'attributes' => $aConfig['span_attributes'] ?? [],
            'pre_hook' => $aConfig['pre_hook'] ?? null,
            'post_hook' => $aConfig['post_hook'] ?? null,
        ];
    }

    /**
     * Retrieves instrumentation configuration
     *
     * @return array<string, mixed> Instrumentation configuration
     */
    public function getInstrumentationConfig(): array
    {
        return $this->aConfig['instrumentation'] ?? [];
    }
}