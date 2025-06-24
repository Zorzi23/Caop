<?php
namespace ObjectFlow\Trait;

/**
 * Trait InstanceTrait
 *
 * Provides methods for creating new instances of a class.
 */
trait InstanceTrait {

    /**
     * @var static
     */
    static $instance;

    /**
     * Create a new instance of the class.
     *
     * @return static
     */
    public static function newInstance() {
        $aInitArgs = func_get_args() ?: [];
        return new static(...$aInitArgs);
    }

}