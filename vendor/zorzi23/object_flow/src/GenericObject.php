<?php
namespace ObjectFlow;

use ObjectFlow\Readers\InstatiableObjectMethodReader;
use ObjectFlow\Trait\InstanceTrait;
use ObjectFlow\Wrappers\ArrayObjectWrapper;

/**
 * Class GenericObject
 *
 * A generic object class that provides dynamic property handling and conversion from arrays/objects.
 */
class GenericObject {

    use InstanceTrait;

    /**
     * @internal
     * @var array
     */
    protected $aPropertys = [];

    /**
     * Create a GenericObject from an array or object.
     *
     * @param array|object $xArrayObject
     * @return static
     */
    public static function fromArrayObject($xArrayObject) {
        $oGenericObject = new static();
        foreach((array) $xArrayObject as $sKey => $xValue) {
            $oGenericObject->__set($sKey, $xValue);
        }
        return $oGenericObject;
    }

    /**
     * Recursively create a GenericObject from an array or object.
     *
     * @param array|object $xArrayObject
     * @return static
     */
    public static function fromArrayObjectRecursive($xArrayObject) {
        $oGeneric = static::fromArrayObject($xArrayObject);
        foreach($oGeneric->data() as $sKey => $xValue) {
            if(is_array($xValue) || is_object($xValue)) {
                $oGeneric->__set($sKey, static::fromArrayObjectRecursive($xValue));
                continue;
            }
        }
        return $oGeneric;
    }

    /**
     * Get all properties as an array.
     *
     * @return array
     */
    public function data() {
        return $this->aPropertys;
    }

    /**
     * Magic method to get a property value.
     *
     * @param string $sProperty
     * @return mixed
     */
    public function __get($sProperty) {
        return $this->getProperty($sProperty);
    }
    
    /**
     * Magic method to set a property value.
     *
     * @param string $sProperty
     * @param mixed $xValue
     * @return $this
     */
    public function __set($sProperty, $xValue) {
        return $this->setProperty($sProperty, $xValue);
    }

    /**
     * Add a value to a property array.
     *
     * @param string $sProperty
     * @param mixed $xKeyValue
     * @param mixed $xValue
     * @return $this
     */
    public function __add($sProperty, $xKeyValue, $xValue = null) {
        return $this->addPropertyValue($sProperty, $xKeyValue, $xValue);
    }

    /**
     * Check if a property value is true.
     *
     * @param string $sProperty
     * @return bool
     */
    public function __is($sProperty) {
        return $this->isPropertyTrue($sProperty);
    }   

    /**
     * Magic method to handle dynamic method calls.
     *
     * @param string $sMethod
     * @param array $xArgs
     * @return mixed
     */
    public function __call($sMethod, $xArgs) {
        return $this->handleDynamicCall($sMethod, $xArgs);
    }

    /**
     * Magic method to provide debug information.
     *
     * @return array
     */
    public function __debugInfo() {
        return $this->aPropertys;
    }

    /**
     * Prevent setting state of the object.
     *
     * @param array $aData
     */
    public static function __set_state($aData) {
        trigger_error(strtr('Cannot __set_state of the current object with: {sData}', [
            '{sData}' => serialize($aData)
        ]));
    }

    /**
     * Get a property value.
     *
     * @param string $sProperty
     * @return mixed
     */
    private function getProperty($sProperty) {
        $xValue = ArrayObjectWrapper::get(lcfirst($sProperty), $this->aPropertys);
        if($xValue !== null) {
            return $xValue;
        }
        return ArrayObjectWrapper::get(ucfirst($sProperty), $this->aPropertys);
    }

    /**
     * Set a property value.
     *
     * @param string $sProperty
     * @param mixed $xValue
     * @return $this
     */
    private function setProperty($sProperty, $xValue) {
        $this->aPropertys = ArrayObjectWrapper::set($sProperty, $xValue, $this->aPropertys);
        return $this; 
    }

    /**
     * Add a value to a property array.
     *
     * @param string $sProperty
     * @param mixed $xKeyValue
     * @param mixed $xValue
     * @return $this
     */
    private function addPropertyValue($sProperty, $xKeyValue, $xValue = null) {
        $aPropertyValue = $this->getProperty($sProperty) ?: [];
        if($xValue) {
            $aPropertyValue[$xKeyValue] = $xValue;
        }
        else {
            $aPropertyValue[] = $xKeyValue;
        }
        $this->aPropertys = ArrayObjectWrapper::set($sProperty, $aPropertyValue, $this->aPropertys);
        return $this; 
    }

    /**
     * Check if a property value is true.
     *
     * @param string $sProperty
     * @return bool
     */
    private function isPropertyTrue($sProperty) {
        $xPropertyValue = $this->getProperty($sProperty);
        return (int) $xPropertyValue === 1;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $sMethod
     * @param array $xArgs
     * @return mixed
     */
    private function handleDynamicCall($sMethod, $xArgs) {
        if(InstatiableObjectMethodReader::isMethodGetter($sMethod)) {
            $sProperty = InstatiableObjectMethodReader::extractPropertyNameFromGetterSetter($sMethod);
            return $this->getProperty($sProperty);
        }
        if(InstatiableObjectMethodReader::isMethodSetter($sMethod)) {
            $sProperty = InstatiableObjectMethodReader::extractPropertyNameFromGetterSetter($sMethod);
            return $this->setProperty($sProperty, ...$xArgs);
        }
        if(InstatiableObjectMethodReader::isMethodByPrefix($sMethod, 'add')) {
            $sProperty = InstatiableObjectMethodReader::extractPropertyNameFromPrefix($sMethod, 'add');
            return $this->addPropertyValue($sProperty, ...$xArgs);
        }
        if(InstatiableObjectMethodReader::isMethodByPrefix($sMethod, 'is')) {
            $sProperty = InstatiableObjectMethodReader::extractPropertyNameFromPrefix($sMethod, 'is');
            return $this->isPropertyTrue($sProperty);
        }
        if(!method_exists($this, $sMethod)) {
            return null;
        }
        return $this->{$sMethod}($xArgs);
    }
}