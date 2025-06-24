<?php
declare(strict_types=1);

namespace CaOp;

use CaOp\MonitoredEntity\MonitoredEntity;
use ObjectFlow\Trait\InstanceTrait;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanInterface;

/**
 * Manages telemetry instrumentation for PHP entities with consistent monitoring
 */
final class EntityTelemetry
{
    use InstanceTrait;

    private static ?CachedInstrumentation $oInstrumentation = null;
    private readonly MonitoredEntityRegistry $oMonitoredEntityRegistry;
    private readonly EntityHookRegister $oEntityHookRegister;
    private readonly ArgumentSanitizer $oArgumentSanitizer;
    /** @var array<string, SpanInterface> */
    private array $aSpanRegistry = [];

    /**
     * Private constructor to enforce factory method usage
     *
     * @param MonitoredEntityRegistry $oMonitoredEntityRegistry Registry for monitored entities
     * @param EntityHookRegister $oEntityHookRegister Entity hook registrar
     * @param ArgumentSanitizer $oArgumentSanitizer Argument sanitizer
     */
    private function __construct(
        MonitoredEntityRegistry $oMonitoredEntityRegistry,
        EntityHookRegister $oEntityHookRegister,
        ArgumentSanitizer $oArgumentSanitizer
    ) {
        $this->oMonitoredEntityRegistry = $oMonitoredEntityRegistry;
        $this->oEntityHookRegister = $oEntityHookRegister;
        $this->oArgumentSanitizer = $oArgumentSanitizer;
    }

    /**
     * Creates an instance with default dependencies and a custom instrumentation name
     *
     * @param string $sInstrumentationName Name of the instrumentation (default: 'app_instrumentation')
     * @return self
     */
    public static function createWithDefaults(string $sInstrumentationName = 'app_instrumentation'): self
    {
        self::$oInstrumentation = new CachedInstrumentation($sInstrumentationName);
        return new self(
            new MonitoredEntityRegistry(),
            new EntityHookRegister(),
            new ArgumentSanitizer()
        );
    }

    /**
     * Registers an entity for telemetry monitoring with optional parent span
     *
     * @param MonitoredEntity $oEntity Entity to monitor
     * @param array<string, mixed> $aOptions Configuration options
     * @param string|null $sParentIdentifier Parent entity identifier (optional)
     * @return self
     */
    public function registerMonitoredEntity(MonitoredEntity $oEntity, array $aOptions = [], ?string $sParentIdentifier = null): self
    {
        $sIdentifier = $oEntity->getFullIdentifier();
        $this->oMonitoredEntityRegistry->add($oEntity);
        $this->oEntityHookRegister->register(
            $oEntity,
            HookConfiguration::createFromOptions(
                $aOptions,
                $sIdentifier,
                self::$oInstrumentation,
                $this->oArgumentSanitizer,
                $sParentIdentifier
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
     * Stores a span for an entity identifier
     *
     * @param string $sIdentifier Entity identifier
     * @param SpanInterface $oSpan Span to store
     */
    public function storeSpan(string $sIdentifier, SpanInterface $oSpan): void
    {
        $this->aSpanRegistry[$sIdentifier] = $oSpan;
    }

    /**
     * Retrieves a span for an entity identifier
     *
     * @param string $sIdentifier Entity identifier
     * @return SpanInterface|null The stored span or null if not found
     */
    public function getSpan(string $sIdentifier): ?SpanInterface
    {
        return $this->aSpanRegistry[$sIdentifier] ?? null;
    }
}