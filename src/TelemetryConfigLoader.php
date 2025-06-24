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
     * @throws \Symfony\Component\Yaml\Exception\ParseException If YAML parsing fails
     */
    public static function createFromYaml(string $sPath): self
    {
        return new self(Yaml::parseFile($sPath));
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
        $oBuilder = MonitoredEntityBuilder::newInstance();

        $this->registerFunctions($oTelemetry, $oBuilder);
        $this->registerMethods($oTelemetry, $oBuilder);
        $this->registerGroups($oTelemetry, $oBuilder);
    }

    /**
     * Registers function entities from configuration
     *
     * @param EntityTelemetry $oTelemetry Telemetry system instance
     * @param MonitoredEntityBuilder $oBuilder Entity builder
     */
    private function registerFunctions(EntityTelemetry $oTelemetry, MonitoredEntityBuilder $oBuilder): void
    {
        foreach ($this->aConfig['entities']['functions'] ?? [] as $aFnConfig) {
            $oEntity = $oBuilder->forFunction($aFnConfig['name']);
            $oTelemetry->registerMonitoredEntity($oEntity, $this->createHookConfig($aFnConfig));
        }
    }

    /**
     * Registers method entities from configuration
     *
     * @param EntityTelemetry $oTelemetry Telemetry system instance
     * @param MonitoredEntityBuilder $oBuilder Entity builder
     */
    private function registerMethods(EntityTelemetry $oTelemetry, MonitoredEntityBuilder $oBuilder): void
    {
        foreach ($this->aConfig['entities']['methods'] ?? [] as $aMethodConfig) {
            $oEntity = $oBuilder->forMethod(
                $aMethodConfig['class'],
                $aMethodConfig['method']
            );
            $oTelemetry->registerMonitoredEntity($oEntity, $this->createHookConfig($aMethodConfig));
        }
    }

    /**
     * Registers group entities from configuration
     *
     * @param EntityTelemetry $oTelemetry Telemetry system instance
     * @param MonitoredEntityBuilder $oBuilder Entity builder
     */
    private function registerGroups(EntityTelemetry $oTelemetry, MonitoredEntityBuilder $oBuilder): void
    {
        foreach ($this->aConfig['entities']['groups'] ?? [] as $sGroupName => $aGroupConfig) {
            $aCommonAttributes = $aGroupConfig['common_attributes'] ?? [];

            foreach ($aGroupConfig['items'] ?? [] as $aItem) {
                if (isset($aItem['class'], $aItem['method'])) {
                    $oEntity = $oBuilder->forMethod(
                        $aItem['class'],
                        $aItem['method'],
                        $aItem['static'] ?? false
                    );
                    $aConfig = $this->createHookConfig($aItem);
                    $aConfig['attributes'] = array_merge($aConfig['attributes'], $aCommonAttributes);
                    $oTelemetry->registerMonitoredEntity($oEntity, $aConfig);
                }
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