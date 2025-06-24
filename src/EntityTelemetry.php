<?php
declare(strict_types=1);
namespace CaOp;
use CaOp\MonitoredEntity\MonitoredEntity;
use ObjectFlow\Trait\InstanceTrait;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;

/**
 * Manages telemetry instrumentation for PHP entities with consistent monitoring
 */
class EntityTelemetry
{
    use InstanceTrait;

    private static ?CachedInstrumentation $oInstrumentation = null;
    private readonly MonitoredEntityRegistry $oMonitoredEntityRegistry;
    private readonly EntityHookRegister $oEntityHookRegister;
    private readonly ArgumentSanitizer $oArgumentSanitizer;

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
     * Creates an instance with a custom MonitoredEntityRegistry
     *
     * @param MonitoredEntityRegistry $oMonitoredEntityRegistry Custom registry for monitored entities
     * @param string $sInstrumentationName Name of the instrumentation (default: 'app_instrumentation')
     * @return self
     */
    public static function createWithCustomRegistry(
        MonitoredEntityRegistry $oMonitoredEntityRegistry,
        string $sInstrumentationName = 'app_instrumentation'
    ): self {
        self::$oInstrumentation = new CachedInstrumentation($sInstrumentationName);
        return new self(
            $oMonitoredEntityRegistry,
            new EntityHookRegister(),
            new ArgumentSanitizer()
        );
    }

    /**
     * Creates an instance with a custom EntityHookRegister
     *
     * @param EntityHookRegister $oEntityHookRegister Custom entity hook registrar
     * @param string $sInstrumentationName Name of the instrumentation (default: 'app_instrumentation')
     * @return self
     */
    public static function createWithCustomHookRegister(
        EntityHookRegister $oEntityHookRegister,
        string $sInstrumentationName = 'app_instrumentation'
    ): self {
        self::$oInstrumentation = new CachedInstrumentation($sInstrumentationName);
        return new self(
            new MonitoredEntityRegistry(),
            $oEntityHookRegister,
            new ArgumentSanitizer()
        );
    }

    /**
     * Creates an instance with a custom ArgumentSanitizer
     *
     * @param ArgumentSanitizer $oArgumentSanitizer Custom argument sanitizer
     * @param string $sInstrumentationName Name of the instrumentation (default: 'app_instrumentation')
     * @return self
     */
    public static function createWithCustomSanitizer(
        ArgumentSanitizer $oArgumentSanitizer,
        string $sInstrumentationName = 'app_instrumentation'
    ): self {
        self::$oInstrumentation = new CachedInstrumentation($sInstrumentationName);
        return new self(
            new MonitoredEntityRegistry(),
            new EntityHookRegister(),
            $oArgumentSanitizer
        );
    }

    /**
     * Registers an entity for telemetry monitoring with optional configuration
     *
     * @param MonitoredEntity $oEntity Entity to monitor
     * @param array<string, mixed> $aOptions Configuration options
     * @return self
     */
    public function registerMonitoredEntity(MonitoredEntity $oEntity, array $aOptions = []): self
    {
        $this->oMonitoredEntityRegistry->add($oEntity);
        $this->oEntityHookRegister->register(
            $oEntity,
            HookConfiguration::createFromOptions(
                $aOptions,
                $oEntity->getFullIdentifier(),
                self::$oInstrumentation,
                $this->oArgumentSanitizer
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
}