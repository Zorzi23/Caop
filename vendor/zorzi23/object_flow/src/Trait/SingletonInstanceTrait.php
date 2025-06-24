<?php
namespace ObjectFlow\Trait;

/**
 * Trait SingletonInstanceTrait
 *
 * Provides a singleton instance method for classes.
 */
trait SingletonInstanceTrait  {

    use CacheableInstanceTrait;

    /**
     * Get the singleton instance of the class.
     *
     * @return static
     */
    public static function singleton() {
        if(!static::getPropFromCache('instance')) {
            static::setPropToCache('instance', static::newInstance());
        }
        return static::getPropFromCache('instance');
    }

}