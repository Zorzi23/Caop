<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

use ObjectFlow\Trait\InstanceTrait;

/**
 * Builds monitored entity instances for telemetry tracking
 */
final class MonitoredEntityBuilder
{
    use InstanceTrait;

    /**
     * Private constructor to enforce factory method usage
     */
    private function __construct()
    {
    }

    /**
     * Creates a new instance of MonitoredEntityBuilder
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Creates a monitored function entity
     *
     * @param string $sFunctionName Name of the function to monitor
     * @return MonitoredFunction The monitored function entity
     */
    public function forFunction(string $sFunctionName): MonitoredFunction
    {
        return MonitoredFunction::function($sFunctionName);
    }

    /**
     * Creates a monitored method entity
     *
     * @param string $sClassName Name of the class containing the method
     * @param string $sMethodName Name of the method to monitor
     * @return MonitoredMethod The monitored method entity
     */
    public function forMethod(string $sClassName, string $sMethodName): MonitoredMethod
    {
        return MonitoredMethod::classMethod($sClassName, $sMethodName);
    }

    /**
     * Creates a monitored entity from a callable
     *
     * @param callable|string $xFrom Callable or function name to create the entity from
     * @return MonitoredEntity The monitored entity (function or method)
     */
    public function from($xFrom): MonitoredEntity
    {
        if (is_array($xFrom)) {
            [$xObject, $sMethod] = $xFrom;
            return $this->forMethod(get_class($xObject), $sMethod);
        }

        return $this->forFunction(is_string($xFrom) ? $xFrom : 'closure');
    }
}