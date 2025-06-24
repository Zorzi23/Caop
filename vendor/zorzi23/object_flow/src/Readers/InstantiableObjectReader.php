<?php
namespace ObjectFlow\Readers;
use RegexFlow\Regex;
use RegexFlow\RegexExpression;

class InstantiableObjectReader {

    /**
     * 
     * @param mixed $oObjectClass
     * @return \ReflectionClassConstant[]
     */
    public static function readConstant($sConstant, $oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getConstant($sConstant);
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return \ReflectionClassConstant[]
     */
    public static function readConstants($oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getConstants();
    }

    /**
     * 
     * @param string $sProperty
     * @param mixed $oObjectClass
     * @return string
     */
    public static function getPropertyModifiersNames($sProperty, $oObjectClass) {
        $oProperty = self::newReflection($oObjectClass)->getProperty($sProperty);
        return implode(', ', \Reflection::getModifierNames($oProperty->getModifiers()));
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return \ReflectionProperty[]
     */
    public static function readPrivatePropertys($oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getProperties(\ReflectionProperty::IS_PRIVATE);
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return \ReflectionProperty[]
     */
    public static function readAllPropertysWithFlowBusinessRule($oObjectClass) {
        return array_filter(self::readAllPropertys($oObjectClass), function($oProperty) {
            return !Regex::match()->execute(RegexExpression::stringPattern(
                $oProperty->getDocComment(),
                '/\@internal/'
            ));
        });
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return \ReflectionProperty[]
     */
    public static function readAllPropertys($oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getProperties();
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return string
     */
    public static function readNamespace($oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getName();
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return string
     */
    public static function readNamespaceName($oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getNamespaceName();
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return string
     */
    public static function readClassName($oObjectClass) {
        return self::newReflection($oObjectClass)
            ->getShortName();
    }

    /**
     * 
     * @param mixed $oObjectClass
     * @return \ReflectionClass
     */
    public static function newReflection($oObjectClass) {
        return new \ReflectionClass($oObjectClass);
    }

}