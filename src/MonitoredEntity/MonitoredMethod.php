<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

/**
 * Represents a monitored method entity in the telemetry system.
 */
final class MonitoredMethod extends MonitoredEntity
{
    /**
     * Name of the class containing the method.
     *
     * @var string
     */
    private string $sClassName;

    /**
     * Constructor.
     *
     * @param string $sClassName Name of the class containing the method.
     * @param string $sMethodName Name of the method to monitor.
     * @param string|null $sCustomIdentifier Optional custom identifier.
     */
    public function __construct(string $sClassName, string $sMethodName, ?string $sCustomIdentifier = null)
    {
        parent::__construct($sMethodName, $sCustomIdentifier);
        $this->sClassName = $sClassName;
    }

    /**
     * Factory method to create a monitored method entity.
     *
     * @param string $sClassName Name of the class containing the method.
     * @param string $sMethodName Name of the method to monitor.
     * @param string|null $sCustomIdentifier Optional custom identifier.
     * @return self
     */
    public static function classMethod(string $sClassName, string $sMethodName): self
    {
        return new self($sClassName, $sMethodName);
    }

    /**
     * Retrieves the name of the method.
     *
     * @return string The method name.
     */
    public function getName(): string
    {
        return $this->sName;
    }

    /**
     * Retrieves the full identifier of the method.
     *
     * @return string The full identifier in the format "ClassName::methodName".
     */
    public function getFullIdentifier(): string
    {
        // If custom identifier is set in parent, use it; otherwise default to ClassName::MethodName
        return $this->sCustomIdentifier ?? "{$this->sClassName}::{$this->sName}";
    }

    /**
     * Retrieves the type of the entity.
     *
     * @return MonitoredEntityType The entity type (METHOD).
     */
    public function getType(): MonitoredEntityType
    {
        return MonitoredEntityType::createMethod();
    }

    /**
     * Retrieves the name of the class containing the method.
     *
     * @return string The class name.
     */
    public function getClassName(): string
    {
        return $this->sClassName;
    }
}
