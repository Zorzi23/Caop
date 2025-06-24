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
            pre: function () use ($oConfig): array {
                return $oConfig->handlePreHook(func_get_args());
            },
            post: function (mixed $xResult, array $aParams, $oScope, ?Throwable $oException) use ($oConfig): mixed {
                return $oConfig->handlePostHook($xResult, $aParams, $oScope, $oException);
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
            pre: function () use ($oConfig): array {
                return $oConfig->handlePreHook(func_get_args());
            },
            post: function () use ($oConfig): mixed {
                return $oConfig->handlePostHook(func_get_args());
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