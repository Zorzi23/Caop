<?php
declare(strict_types=1);

namespace CaOp;

use CaOp\MonitoredEntity\MonitoredEntity;
use CaOp\MonitoredEntity\MonitoredEntityType;
use CaOp\MonitoredEntity\MonitoredFunction;
use CaOp\MonitoredEntity\MonitoredMethod;
use Throwable;

/**
 * Manages registration of telemetry hooks for monitored entities
 */
final class EntityHookRegister
{
    /**
     * Creates a new instance of EntityHookRegister
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Registers a monitored entity with its telemetry hook configuration
     *
     * @param MonitoredEntity $oEntity Entity to register
     * @param HookConfiguration $oConfig Hook configuration
     */
    public function register(MonitoredEntity $oEntity, HookConfiguration $oConfig): void
    {
        $aRegisterMethods = $this->getEntityTypeRegisterMethods();
        $fnRegister = $aRegisterMethods[$oEntity->getType()->getValue()] ?? null;

        if ($fnRegister) {
            call_user_func($fnRegister, $oEntity, $oConfig);
        } else {
            error_log("EntityHookRegister: No registration method found for entity type {$oEntity->getType()->getValue()}");
        }
    }

    /**
     * Registers a method hook with telemetry
     *
     * @param MonitoredMethod $oMethod Monitored method entity
     * @param HookConfiguration $oConfig Hook configuration
     */
    private function registerMethod(MonitoredMethod $oMethod, HookConfiguration $oConfig): void
    {
        \OpenTelemetry\Instrumentation\hook(
            class: $oMethod->getClassName(),
            function: $oMethod->getName(),
            pre: function ($xObject, $aParams) use ($oConfig): array {
                $oConfig->handlePreHook(func_get_args());
                unset($xObject);
                return $aParams;
            },
            post: function ($xObject, array $aParams, $xResult, ?Throwable $oException) use ($oConfig): mixed {
                return $oConfig->handlePostHook($xObject, $aParams, $xResult, $oException);
            }
        );
    }

    /**
     * Registers a function hook with telemetry
     *
     * @param MonitoredFunction $oFunction Monitored function entity
     * @param HookConfiguration $oConfig Hook configuration
     */
    private function registerFunction(MonitoredFunction $oFunction, HookConfiguration $oConfig): void
    {
        \OpenTelemetry\Instrumentation\hook(
            class: null,
            function: $oFunction->getName(),
            pre: function ($xObject, $aParams) use ($oConfig): array {
                $oConfig->handlePreHook(func_get_args());
                unset($xObject);
                return $aParams;
            },
            post: function ($xObject, array $aParams, $xResult, ?Throwable $oException) use ($oConfig): mixed {
                return $oConfig->handlePostHook($xObject, $aParams, $xResult, $oException);
            }
        );
    }

    /**
     * Returns the mapping of entity types to their registration methods
     *
     * @return array<string, callable> Mapping of entity type values to registration methods
     */
    private function getEntityTypeRegisterMethods(): array
    {
        return [
            MonitoredEntityType::METHOD => [$this, 'registerMethod'],
            MonitoredEntityType::FUNCTION => [$this, 'registerFunction'],
        ];
    }
}