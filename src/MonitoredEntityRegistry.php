<?php
namespace CaOp;

use CaOp\MonitoredEntity\MonitoredEntity;

/**
 * Manages the registry of monitored functions
 */
class MonitoredEntityRegistry
{
    private array $aMonitoredFunctions = [];

    /**
     * Adds a function to the registry
     *
     * @param string $sFunctionName Function name to add
     * @return void
     */
    public function add(MonitoredEntity $oMonitoredEntity): void
    {
        $this->aMonitoredFunctions[$oMonitoredEntity->getFullIdentifier()] = true;
    }

    /**
     * Checks if a function is in the registry
     *
     * @param string $sFunctionName Function name to check
     * @return bool
     */
    public function has(string $sFunctionName): bool
    {
        return isset($this->aMonitoredFunctions[$sFunctionName]);
    }

    /**
     * Gets all monitored function names
     *
     * @return array
     */
    public function getAll(): array
    {
        return array_keys($this->aMonitoredFunctions);
    }
}