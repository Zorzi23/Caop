<?php
declare(strict_types=1);

namespace CaOp;

use CaOp\MonitoredEntity\MonitoredEntity;
use ObjectFlow\Trait\InstanceTrait;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\Context;

/**
 * Manages telemetry instrumentation for PHP entities with consistent monitoring
 */
final class EntityTelemetry
{
    use InstanceTrait;

    private static ?CachedInstrumentation $oInstrumentation = null;
    private readonly MonitoredEntityRegistry $oMonitoredEntityRegistry;
    private readonly EntityHookRegister $oEntityHookRegister;
    private readonly AttributesCollector $oAttributesCollector;
    /** @var array<string, Context> */
    private array $aSpanContextRegistry = [];

    /**
     * Private constructor to enforce factory method usage
     *
     * @param MonitoredEntityRegistry $oMonitoredEntityRegistry Registry for monitored entities
     * @param EntityHookRegister $oEntityHookRegister Entity hook registrar
     * @param AttributesCollector $oAttributesCollector Attribute collector
     */
    private function __construct(
        MonitoredEntityRegistry $oMonitoredEntityRegistry,
        EntityHookRegister $oEntityHookRegister,
        AttributesCollector $oAttributesCollector
    ) {
        $this->oMonitoredEntityRegistry = $oMonitoredEntityRegistry;
        $this->oEntityHookRegister = $oEntityHookRegister;
        $this->oAttributesCollector = $oAttributesCollector;
    }

    /**
     * Creates an instance with default dependencies and a custom instrumentation name
     *
     * @param string $sInstrumentationName Name of the instrumentation
     * @param array<string, mixed> $aInstrumentationConfig Instrumentation configuration
     * @return self
     */
    public static function createWithDefaults(
        string $sInstrumentationName = 'app_instrumentation',
        array $aInstrumentationConfig = []
    ): self {
        self::$oInstrumentation = new CachedInstrumentation(
            $sInstrumentationName,
            $aInstrumentationConfig['trace_exporter'] ?? 'console'
        );
        return new self(
            new MonitoredEntityRegistry(),
            EntityHookRegister::create(),
            new AttributesCollector()
        );
    }

    /**
     * Registers an entity for telemetry monitoring with optional parent span
     *
     * @param MonitoredEntity $oEntity Entity to monitor
     * @param array<string, mixed> $aOptions Configuration options
     * @param string|null $sParentIdentifier Parent entity identifier
     * @return self
     */
    public function registerMonitoredEntity(
        MonitoredEntity $oEntity,
        array $aOptions = [],
        ?string $sParentIdentifier = null
    ): self {
        $sIdentifier = $oEntity->getFullIdentifier();
        if ($sParentIdentifier && !$this->oMonitoredEntityRegistry->has($sParentIdentifier)) {
            error_log("EntityTelemetry: Parent entity {$sParentIdentifier} not found for {$sIdentifier}");
            $sParentIdentifier = null;
        }
        $this->oMonitoredEntityRegistry->add($oEntity);
        $this->oEntityHookRegister->register(
            $oEntity,
            HookConfiguration::createFromOptions(
                $aOptions,
                $sIdentifier,
                self::$oInstrumentation,
                $this->oAttributesCollector,
                $sParentIdentifier,
                $this
            )
        );
        return $this;
    }

    /**
     * Checks if an entity is currently monitored
     *
     * @param string $sEntityName Entity name to check
     * @return bool True if the entity is monitored, false otherwise
     */
    public function isEntityMonitored(string $sEntityName): bool
    {
        return $this->oMonitoredEntityRegistry->has($sEntityName);
    }

    /**
     * Retrieves all monitored entities
     *
     * @return array<MonitoredEntity> List of monitored entities
     */
    public function getAllMonitoredEntities(): array
    {
        return $this->oMonitoredEntityRegistry->getAll();
    }

    /**
     * Stores a span's context for an entity identifier
     *
     * @param string $sIdentifier Entity identifier
     * @param SpanInterface $oSpan Span to store
     */
    public function storeSpan(string $sIdentifier, SpanInterface $oSpan): void
    {
        $this->aSpanContextRegistry[$sIdentifier] = $oSpan->storeInContext(Context::getCurrent());
    }

    /**
     * Retrieves a span's context for an entity identifier
     *
     * @param string $sIdentifier Entity identifier
     * @return Context|null The stored span context or null if not found
     */
    public function getSpanContext(string $sIdentifier): ?Context
    {
        return $this->aSpanContextRegistry[$sIdentifier] ?? null;
    }
}