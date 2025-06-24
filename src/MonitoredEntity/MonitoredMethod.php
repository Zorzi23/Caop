<?php
declare(strict_types=1);
namespace CaOp\MonitoredEntity;

/**
 * Represents a monitored method entity in the telemetry system
 */
final class MonitoredMethod implements MonitoredEntity
{
    private string $sClassName;
    private string $sMethodName;

    /**
     * Private constructor to enforce factory method usage
     *
     * @param string $sClassName Name of the class containing the method
     * @param string $sMethodName Name of the method to monitor
     */
    private function __construct(string $sClassName, string $sMethodName)
    {
        $this->sClassName = $sClassName;
        $this->sMethodName = $sMethodName;
    }

    /**
     * Creates a monitored method entity
     *
     * @param string $sClassName Name of the class containing the method
     * @param string $sMethodName Name of the method to monitor
     * @return self
     */
    public static function classMethod(string $sClassName, string $sMethodName): self
    {
        return new self($sClassName, $sMethodName);
    }

    /**
     * Retrieves the name of the method
     *
     * @return string The method name
     */
    public function getName(): string
    {
        return $this->sMethodName;
    }

    /**
     * Retrieves the full identifier of the method
     *
     * @return string The full identifier in the format "ClassName::methodName"
     */
    public function getFullIdentifier(): string
    {
        return "{$this->sClassName}::{$this->sMethodName}";
    }

    /**
     * Retrieves the type of the entity
     *
     * @return MonitoredEntityType The entity type (METHOD)
     */
    public function getType(): MonitoredEntityType
    {
        return MonitoredEntityType::createMethod();
    }

    /**
     * Retrieves the name of the class containing the method
     *
     * @return string The class name
     */
    public function getClassName(): string
    {
        return $this->sClassName;
    }
}