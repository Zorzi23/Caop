<?php

declare(strict_types=1);

namespace CaOp;

use ObjectFlow\GenericObject;
use ObjectFlow\Trait\InstanceTrait;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use CaOp\MonitoredEntity\MonitoredEntityBuilder;

/**
 * Loads and manages telemetry configuration from YAML files.
 */
final class TelemetryConfigLoader
{
    use InstanceTrait;

    /** @var array<string, mixed> */
    private array $aConfig;

    /**
     * @param array<string, mixed> $aConfig
     */
    private function __construct(array $aConfig)
    {
        $this->aConfig = $aConfig;
    }

    /**
     * Loads configuration from a YAML file.
     *
     * @param string $sPath Path to the YAML file
     * @return self
     */
    public static function createFromYaml(string $sPath): self
    {
        if (!file_exists($sPath)) {
            error_log("TelemetryConfigLoader: File not found: {$sPath}");
            return new self([]);
        }

        try {
            $aParsed = Yaml::parseFile($sPath);
            return new self($aParsed);
        } catch (ParseException $oException) {
            error_log("TelemetryConfigLoader: YAML parse error: {$oException->getMessage()}");
            return new self([]);
        }
    }

    /**
     * Loads configuration from an array.
     *
     * @param array<string, mixed> $aConfig
     * @return self
     */
    public static function createFromArray(array $aConfig): self
    {
        return new self($aConfig);
    }

    /**
     * Returns the raw config array.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->aConfig;
    }

    /**
     * Converts the config to a GenericObject wrapper.
     *
     * @return GenericObject
     */
    public function toGenericObject(): GenericObject
    {
        return GenericObject::fromArrayObject($this->aConfig);
    }

    /**
     * Registers all entities in the config into the telemetry system.
     *
     * @param EntityTelemetry $oTelemetry
     */
    public function registerAllEntities(EntityTelemetry $oTelemetry): void
    {
        $oBuilder = MonitoredEntityBuilder::create();
        $aFunctions = $this->aConfig['entities']['functions'] ?? [];
        $this->registerEntities($oTelemetry, $oBuilder, $aFunctions, null);
    }

    /**
     * Recursively registers all entities and children.
     *
     * @param EntityTelemetry $oTelemetry
     * @param MonitoredEntityBuilder $oBuilder
     * @param array<int, array<string, mixed>> $aEntities
     * @param string|null $sParentIdentifier
     */
    private function registerEntities(
        EntityTelemetry $oTelemetry,
        MonitoredEntityBuilder $oBuilder,
        array $aEntities,
        ?string $sParentIdentifier
    ): void {
        foreach ($aEntities as $aEntityConfig) {
            $sIdentifier = null;

            if (empty($aEntityConfig['name'])) {
                error_log('TelemetryConfigLoader: Skipping entity with missing name');
                continue;
            }

            if (empty($aEntityConfig['class'])) {
                $oEntity = $oBuilder->forFunction($aEntityConfig['name']);
            } elseif (!empty($aEntityConfig['method'])) {
                $oEntity = $oBuilder->forMethod($aEntityConfig['class'], $aEntityConfig['method']);
            } else {
                error_log("TelemetryConfigLoader: Missing method name for class {$aEntityConfig['class']}");
                continue;
            }

            $sIdentifier = $oEntity->getFullIdentifier();
            $aHookConfig = $this->createHookConfig($aEntityConfig);

            $oTelemetry->registerMonitoredEntity($oEntity, $aHookConfig, $sParentIdentifier);

            if (!empty($aEntityConfig['children']) && is_array($aEntityConfig['children'])) {
                $this->registerEntities($oTelemetry, $oBuilder, $aEntityConfig['children'], $sIdentifier);
            }
        }
    }

    /**
     * Extracts the hook configuration from an entity config array.
     *
     * @param array<string, mixed> $aConfig
     * @return array<string, mixed>
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
     * Returns instrumentation-specific configuration.
     *
     * @return array<string, mixed>
     */
    public function getInstrumentationConfig(): array
    {
        return $this->aConfig['instrumentation'] ?? [];
    }
}
