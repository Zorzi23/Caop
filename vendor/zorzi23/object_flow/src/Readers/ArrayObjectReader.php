<?php
namespace ObjectFlow\Readers;

class ArrayObjectReader {

    /**
     * 
     * @param array|object $xArrayObject
     * @return bool
     */
    public static function isArray($xArrayObject) {
        return is_array($xArrayObject);
    }

    /**
     * 
     * @param array|object $xArrayObject
     * @return bool
     */
    public static function isObject($xArrayObject) {
        return is_object($xArrayObject);
    }

    /**
     * 
     * @param array|object $xArrayObject
     * @return \Iterator
     */
    public static function iterator($xArrayObject) {
        return (new \ArrayObject($xArrayObject))->getIterator();
    }

    /**
     * 
     * @param string $sProperty
     * @param array|object $xArrayObject
     * @return mixed
     */
    public static function get($sProperty, $xArrayObject) {
        $oArrayObject = new \ArrayObject($xArrayObject);
        return $oArrayObject->offsetExists($sProperty)
            ? $oArrayObject->offsetGet($sProperty)
            : null;
    }

}