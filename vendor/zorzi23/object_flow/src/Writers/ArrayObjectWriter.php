<?php
namespace ObjectFlow\Writers;
use ObjectFlow\Readers\ArrayObjectReader;

class ArrayObjectWriter {

    /**
     * 
     * @param string $sProperty
     * @param mixed $xValue
     * @param array|object $xArrayObject
     * @return array|object
     */
    public static function set($sProperty, $xValue, $xArrayObject) {
        $oArrayObject = new \ArrayObject($xArrayObject);
        $oArrayObject->offsetSet($sProperty, $xValue);
        return ArrayObjectReader::isObject($xArrayObject)
            ? self::castObject($oArrayObject->getArrayCopy())
            : $oArrayObject->getArrayCopy();
    }

    /**
     * 
     * @param array|object $xArrayObject
     * @return array
     */
    public static function castArray($xArrayObject) {
        return (array) $xArrayObject;
    }

    /**
     * 
     * @param array|object $xArrayObject
     * @return object
     */
    public static function castObject($xArrayObject) {
        return (object) $xArrayObject;
    }

}