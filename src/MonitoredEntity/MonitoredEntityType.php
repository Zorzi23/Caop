<?php
declare(strict_types=1);

namespace CaOp\MonitoredEntity;

/**
 * Represents types of monitored entities in the telemetry system
 */
final class MonitoredEntityType
{
    public const FUNCTION = 'function';
    public const METHOD = 'method';
    public const CLOSURE = 'closure';
    public const STATIC_METHOD = 'static_method';

    private string $sValue;

    /**
     * Private constructor to enforce factory method usage
     *
     * @param string $sValue Entity type value
     */
    private function __construct(string $sValue)
    {
        $this->sValue = $sValue;
    }

    /**
     * Creates a Function type instance
     *
     * @return self
     */
    public static function createFunction(): self
    {
        return new self(self::FUNCTION);
    }

    /**
     * Creates a Method type instance
     *
     * @return self
     */
    public static function createMethod(): self
    {
        return new self(self::METHOD);
    }

    /**
     * Creates a Closure type instance
     *
     * @return self
     */
    public static function createClosure(): self
    {
        return new self(self::CLOSURE);
    }

    /**
     * Creates a Static Method type instance
     *
     * @return self
     */
    public static function createStaticMethod(): self
    {
        return new self(self::STATIC_METHOD);
    }

    /**
     * Creates an instance from a callable
     *
     * @param callable $xCallable Callable to determine the type from
     * @return self
     */
    public static function fromCallable(callable $xCallable): self
    {
        if (is_array($xCallable)) {
            return is_string($xCallable[0]) ? self::createStaticMethod() : self::createMethod();
        }

        return $xCallable instanceof \Closure ? self::createClosure() : self::createFunction();
    }

    /**
     * Checks if the type is a method (including static methods)
     *
     * @return bool True if the type is METHOD or STATIC_METHOD
     */
    public function isMethod(): bool
    {
        return in_array($this->sValue, [self::METHOD, self::STATIC_METHOD], true);
    }

    /**
     * Checks if the type is a function (including closures)
     *
     * @return bool True if the type is FUNCTION or CLOSURE
     */
    public function isFunction(): bool
    {
        return !$this->isMethod();
    }

    /**
     * Gets the string value of the type
     *
     * @return string Type value
     */
    public function getValue(): string
    {
        return $this->sValue;
    }
}