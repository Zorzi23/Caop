<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

/**
 * Defines the contract for monitored entities in the telemetry system
 */
interface MonitoredEntity
{
    /**
     * Retrieves the basic name of the entity (function or method name)
     *
     * @return string The name of the function or method
     */
    public function getName(): string;

    /**
     * Retrieves the full identifier of the entity
     *
     * For functions: "function_name"
     * For methods: "ClassName::methodName"
     *
     * @return string The full identifier of the entity
     */
    public function getFullIdentifier(): string;

    /**
     * Retrieves the type of the entity
     *
     * @return MonitoredEntityType The entity type (e.g., FUNCTION, METHOD, CLOSURE, STATIC_METHOD)
     */
    public function getType(): MonitoredEntityType;
}