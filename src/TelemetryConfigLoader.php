<?php

declare(strict_types=1);

namespace CaOp;

use ObjectFlow\GenericObject;
use ObjectFlow\Trait\InstanceTrait;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use CaOp\MonitoredEntity\MonitoredEntityBuilder;

/**
 * Loads and manages telemetry configuration from YAML files, supporting entity-level includes.
 *
 * @package CaOp
 */
final class TelemetryConfigLoader
{
    use InstanceTrait;

    /**
     * @var array<string, mixed> $aConfig The parsed configuration array
     */
    private array $aConfig;

    /**
     * Private constructor to enforce factory method usage.
     *
     * @param array<string, mixed> $aConfig The configuration array
     */
    private function __construct(array $aConfig)
    {
        $this->aConfig = $aConfig;
    }

    /**
     * Creates an instance from a YAML file, processing root-level includes.
     *
     * @param string $sPath The path to the YAML file
     * @return self
     * @throws \RuntimeException If the YAML file cannot be parsed
     */
    public static function createFromYaml(string $sPath): self
    {
        if (!file_exists($sPath)) {
            error_log("TelemetryConfigLoader: File not found: {$sPath}");
            return new self(['path' => $sPath]);
        }

        try {
            $aParsedConfig = Yaml::parseFile($sPath);
            $aConfig = $aParsedConfig;
            $aConfig['path'] = $sPath;

            if (!empty($aParsedConfig['include']) && is_array($aParsedConfig['include'])) {
                $aConfig = self::mergeRootIncludedFiles($aParsedConfig, dirname($sPath));
            }

            return new self($aConfig);
        } catch (ParseException $oException) {
            error_log("TelemetryConfigLoader: YAML parse error: {$oException->getMessage()}");
            return new self(['path' => $sPath]);
        }
    }

    /**
     * Merges configurations from included files at the root level.
     *
     * @param array<string, mixed> $aMainConfig The main configuration array
     * @param string $sBasePath The base path for resolving relative file paths
     * @return array<string, mixed> The merged configuration array
     */
    private static function mergeRootIncludedFiles(array $aMainConfig, string $sBasePath): array
    {
        $aMergedConfig = $aMainConfig;
        $aMergedEntities = $aMainConfig['entities'] ?? [];
        $aMergedInstrumentation = $aMainConfig['instrumentation'] ?? [];

        foreach ($aMainConfig['include'] as $aInclude) {
            $sFilePath = $aInclude['file'] ?? '';
            $sFullPath = $sBasePath . DIRECTORY_SEPARATOR . $sFilePath;

            if (!file_exists($sFullPath)) {
                error_log("TelemetryConfigLoader: Included file not found: {$sFullPath}");
                continue;
            }

            try {
                $aIncludedConfig = Yaml::parseFile($sFullPath);
                if (!empty($aIncludedConfig['entities']) && is_array($aIncludedConfig['entities'])) {
                    $aMergedEntities = array_merge($aMergedEntities, $aIncludedConfig['entities']);
                }
                if (!empty($aIncludedConfig['instrumentation']) && is_array($aIncludedConfig['instrumentation'])) {
                    $aMergedInstrumentation = array_merge(
                        $aMergedInstrumentation,
                        $aIncludedConfig['instrumentation']
                    );
                }
                if (!empty($aIncludedConfig['service_name']) && empty($aMergedConfig['service_name'])) {
                    $aMergedConfig['service_name'] = $aIncludedConfig['service_name'];
                }
            } catch (ParseException $oException) {
                error_log("TelemetryConfigLoader: YAML parse error in included file {$sFullPath}: {$oException->getMessage()}");
            }
        }

        $aMergedConfig['entities'] = $aMergedEntities;
        $aMergedConfig['instrumentation'] = $aMergedInstrumentation;
        return $aMergedConfig;
    }

    /**
     * Creates an instance from an array configuration.
     *
     * @param array<string, mixed> $aConfig The configuration array
     * @return self
     */
    public static function createFromArray(array $aConfig): self
    {
        return new self($aConfig);
    }

    /**
     * Returns the raw configuration array.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->aConfig;
    }

    /**
     * Converts the configuration to a GenericObject wrapper.
     *
     * @return \ObjectFlow\GenericObject
     */
    public function toGenericObject(): GenericObject
    {
        return GenericObject::fromArrayObject($this->aConfig);
    }

    /**
     * Registers all entities in the configuration into the telemetry system.
     *
     * @param \CaOp\EntityTelemetry $oTelemetry The telemetry instance
     * @throws \Throwable If an error occurs during entity registration
     */
    public function registerAllEntities(EntityTelemetry $oTelemetry): void
    {
        try {
            $oBuilder = MonitoredEntityBuilder::create();
            $aEntities = $this->aConfig['entities'] ?? [];
            // $aInstrumentationConfig = $this->getInstrumentationConfig();
            // $sServiceName = $this->aConfig['service_name'] ?? 'default-service';

            // EntityTelemetry::setServiceName($sServiceName);

            $this->registerEntities($oTelemetry, $oBuilder, $aEntities, null);
        } catch (\Throwable $oException) {
            error_log("Fatal error on register: {$oException->__toString()}");
        }
    }

    /**
     * Recursively registers entities and their children, including from included files.
     *
     * @param \CaOp\EntityTelemetry $oTelemetry The telemetry instance
     * @param \CaOp\MonitoredEntity\MonitoredEntityBuilder $oBuilder The entity builder
     * @param array<int, array<string, mixed>> $aEntities The entities to register
     * @param string|null $sParentIdentifier The parent entity identifier
     */
    private function registerEntities(
        EntityTelemetry $oTelemetry,
        MonitoredEntityBuilder $oBuilder,
        array $aEntities,
        ?string $sParentIdentifier
    ): void {
        foreach ($aEntities as $aEntityConfig) {
            $sIdentifier = null;

            if (empty($aEntityConfig['class'])) {
                if (empty($aEntityConfig['name'])) {
                    error_log('TelemetryConfigLoader: Skipping entity with missing name');
                    continue;
                }
                $oEntity = $oBuilder->forFunction($aEntityConfig['name']);
            } elseif (!empty($aEntityConfig['method'])) {
                $oEntity = $oBuilder->forMethod($aEntityConfig['class'], $aEntityConfig['method']);
            } else {
                error_log("TelemetryConfigLoader: Missing method name for class {$aEntityConfig['class']}");
                continue;
            }

            $sCustomIdentifier = $aEntityConfig['span_name'] ?? null;
            if ($sCustomIdentifier !== null) {
                $oEntity->setCustomIdentifier($sCustomIdentifier);
            }

            $sIdentifier = $oEntity->getFullIdentifier();
            $aHookConfig = $this->createHookConfig($aEntityConfig);

            $oTelemetry->registerMonitoredEntity($oEntity, $aHookConfig, $sParentIdentifier);

            $aChildEntities = $aEntityConfig['children'] ?? [];
            if (!empty($aEntityConfig['include']) && is_array($aEntityConfig['include'])) {
                $aIncludedEntities = $this->loadIncludedEntities($aEntityConfig['include'], dirname($this->aConfig['path'] ?? '.'));
                $aChildEntities = array_merge($aChildEntities, $aIncludedEntities);
            }

            if (!empty($aChildEntities) && is_array($aChildEntities)) {
                $this->registerEntities($oTelemetry, $oBuilder, $aChildEntities, $sIdentifier);
            }
        }
    }

    /**
     * Loads entities from included files as children.
     *
     * @param array<int, array<string, mixed>> $aIncludes The include directives
     * @param string $sBasePath The base path for resolving relative file paths
     * @return array<int, array<string, mixed>> The included entities
     */
    private function loadIncludedEntities(array $aIncludes, string $sBasePath): array
    {
        $aIncludedEntities = [];

        foreach ($aIncludes as $aInclude) {
            $sFilePath = $aInclude['file'] ?? '';
            $sFullPath = $sBasePath . DIRECTORY_SEPARATOR . $sFilePath;

            if (!file_exists($sFullPath)) {
                error_log("TelemetryConfigLoader: Included file not found: {$sFullPath}");
                continue;
            }

            try {
                $aIncludedConfig = Yaml::parseFile($sFullPath);
                if (!empty($aIncludedConfig['entities']) && is_array($aIncludedConfig['entities'])) {
                    $aIncludedEntities = array_merge($aIncludedEntities, $aIncludedConfig['entities']);
                }
                if (!empty($aIncludedConfig['service_name']) && empty($this->aConfig['service_name'])) {
                    $this->aConfig['service_name'] = $aIncludedConfig['service_name'];
                }
            } catch (ParseException $oException) {
                error_log("TelemetryConfigLoader: YAML parse error in included file {$sFullPath}: {$oException->getMessage()}");
            }
        }

        return $aIncludedEntities;
    }

    /**
     * Creates a hook configuration from an entity config array.
     *
     * @param array<string, mixed> $aConfig The entity configuration
     * @return array<string, mixed> The hook configuration
     */
    private function createHookConfig(array $aConfig): array
    {
        return [
            'span_name' => $aConfig['span_name'] ?? null,
            'attributes' => array_merge(
                $aConfig['span_attributes'] ?? [],
                ['description' => $aConfig['desc'] ?? '']
            ),
            'sensitive_params' => $aConfig['sensitive_params'] ?? [],
            'sensitive_return' => $aConfig['sensitive_return'] ?? false,
            'pre_hook' => $aConfig['pre_hook'] ?? null,
            'post_hook' => $aConfig['post_hook'] ?? null,
            'capture_args' => $aConfig['capture_args'] ?? true,
            'capture_return' => $aConfig['capture_return'] ?? true,
            'capture_errors' => $aConfig['capture_errors'] ?? true,
        ];
    }

    /**
     * Returns the instrumentation-specific configuration.
     *
     * @return array<string, mixed>
     */
    public function getInstrumentationConfig(): array
    {
        return $this->aConfig['instrumentation'] ?? [];
    }
}
