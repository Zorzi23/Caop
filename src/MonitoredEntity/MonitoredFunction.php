<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

/**
 * Represents a monitored function entity in the telemetry system
 */
final class MonitoredFunction extends MonitoredEntity
{
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
     * Retrieves the type of the entity
     *
     * @return MonitoredEntityType The entity type (FUNCTION)
     */
    public function getType(): MonitoredEntityType
    {
        return MonitoredEntityType::createFunction();
    }
}