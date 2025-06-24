<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

/**
 * Represents a monitored function entity in the telemetry system
 */
final class MonitoredFunction implements MonitoredEntity
{
    private string $sFunctionName;

    /**
     * Private constructor to enforce factory method usage
     *
     * @param string $sFunctionName Name of the function to monitor
     */
    public function __construct(string $sFunctionName)
    {
        $this->sFunctionName = $sFunctionName;
    }

    /**
     * Creates a monitored function entity
     *
     * @param string $sFunctionName Name of the function to monitor
     * @return self
     */
    public static function function(string $sFunctionName): self
    {
        return new self($sFunctionName);
    }

    /**
     * Retrieves the name of the function
     *
     * @return string The function name
     */
    public function getName(): string
    {
        return $this->sFunctionName;
    }

    /**
     * Retrieves the full identifier of the function
     *
     * @return string The function name as its full identifier
     */
    public function getFullIdentifier(): string
    {
        return $this->sFunctionName;
    }

    /**
     * Retrieves the type of the entity
     *
     * @return MonitoredEntityType The entity type (FUNCTION)
     */
    public function getType(): MonitoredEntityType
    {
        return MonitoredEntityType::createFunction();
    }
}