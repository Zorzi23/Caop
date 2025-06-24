<?php
namespace ObjectFlow\Trait;

use ObjectFlow\GenericObject;

/**
 * Trait CacheableInstanceTrait
 *
 * Provides methods for caching instance properties.
 */
trait CacheableInstanceTrait  {

    use InstanceTrait;

    /**
     * @var array
     */
    protected static $aCache = [];

    /**
     * Wrap a property with a default value or a callable.
     *
     * @param string $sProperty
     * @param mixed $xDefaultValues
     * @return mixed
     */
    public function wrapProperty($sProperty, $xDefaultValues = null) {
        $xPropValue = $this->getPropFromCache($sProperty);
        if($xPropValue) {
            return $xPropValue;
        }
        if(is_callable($xDefaultValues)) {
            $xPropValue = call_user_func($xDefaultValues);
            $this->setPropToCache($sProperty, $xPropValue);
            return $xPropValue;
        }
        $xPropValue = $xDefaultValues;
        $this->setPropToCache($sProperty, $xPropValue);
        return $xPropValue;
    }

    /**
     * Get a property value or set it to a default value if not set.
     *
     * @param string $sProperty
     * @param array|callable $xDefaultValues
     * @return mixed
     */
    public function propCoalesce($sProperty, $xDefaultValues = null) {
        $xPropValue = $this->{$sProperty};
        if($xPropValue) {
            return $xPropValue;
        }
        if(is_callable($xDefaultValues)) {
            $xPropValue = call_user_func($xDefaultValues);
            $this->{$sProperty} = $xPropValue;
            return $xPropValue;
        }
        foreach($xDefaultValues as $xDefaultValue) {
            if(!$xDefaultValue) { continue; }
            $xPropValue = $xDefaultValue;
            break;
        }
        $this->{$sProperty} = $xPropValue;
        return $xPropValue;
    }

    /**
     * Get a property from the cache.
     *
     * @param mixed $sProp
     * @return mixed
     */
    public static function getPropFromCache($sProp) {
        return static::getCacheAsGenericObject()->__get($sProp);
    }

    /**
     * Get the cache as a GenericObject.
     *
     * @return GenericObject
     */
    public static function getCacheAsGenericObject() {
        return GenericObject::fromArrayObject(static::$aCache);
    }

    /**
     * Set a property to the cache.
     *
     * @param mixed $sProp
     * @param mixed $xValue
     * @return string
     */
    public static function setPropToCache($sProp, $xValue) {
        $oCache = static::getCacheAsGenericObject();
        $oCache->__set($sProp, $xValue);
        static::$aCache = $oCache->data();
        return static::class;
    }

}