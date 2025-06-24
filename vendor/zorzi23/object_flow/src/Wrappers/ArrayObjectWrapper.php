<?php
namespace ObjectFlow\Wrappers;
use ObjectFlow\Readers\ArrayObjectReader;
use ObjectFlow\Writers\ArrayObjectWriter;

class ArrayObjectWrapper {

    /**
     * 
     * @param string $sProperty
     * @param array|object $xArrayObject
     * @return mixed
     */
    public static function get($sProperty, $xArrayObject) {
        return ArrayObjectReader::get($sProperty, $xArrayObject);
    }

    /**
     * 
     * @param string $sProperty
     * @param mixed $xValue
     * @param array|object $xArrayObject
     * @return mixed
     */
    public static function set($sProperty, $xValue, $xArrayObject) {
        return ArrayObjectWriter::set($sProperty, $xValue, $xArrayObject);
    }

}