<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

/**
 * Abstract base class for monitored entities in the telemetry system.
 */
abstract class MonitoredEntity
{
    /**
     * Optional custom identifier for the monitored entity.
     *
     * @var string|null
     */
    protected ?string $sCustomIdentifier;

    /**
     * The basic name of the entity (function or method name).
     *
     * @var string
     */
    protected string $sName;

    /**
     * Constructor.
     *
     * @param string $sName The name of the function or method.
     * @param string|null $sCustomIdentifier Optional custom identifier.
     */
    public function __construct(string $sName, ?string $sCustomIdentifier = null)
    {
        $this->sName = $sName;
        $this->sCustomIdentifier = $sCustomIdentifier;
    }

    /**
     * Retrieves the basic name of the entity (function or method name).
     *
     * @return string The name of the function or method.
     */
    public function getName(): string
    {
        return $this->sName;
    }

    /**
     * Retrieves the full identifier of the entity.
     *
     * For functions: "function_name"
     * For methods: "ClassName::methodName"
     * For custom spans: "example"
     *
     * @return string The full identifier of the entity.
     */
    public function getFullIdentifier(): string
    {
        return $this->sCustomIdentifier ?? $this->sName;
    }

    /**
     * Retrieves the type of the entity.
     *
     * @return MonitoredEntityType The entity type (e.g., FUNCTION, METHOD, etc.)
     */
    abstract public function getType(): MonitoredEntityType;

    /**
     * Get the value of sCustomIdentifier
     *
     * @return ?string
     */
    public function getCustomIdentifier(): ?string
    {
        return $this->sCustomIdentifier;
    }

    /**
     * Set the value of sCustomIdentifier
     *
     * @param ?string $sCustomIdentifier
     * @return self
     */
    public function setCustomIdentifier(?string $sCustomIdentifier): self
    {
        $this->sCustomIdentifier = $sCustomIdentifier;
        return $this;
    }
}
