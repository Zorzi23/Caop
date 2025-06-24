<?php
namespace ObjectFlow\Readers;
use RegexFlow\Regex;
use RegexFlow\RegexExpression;

class InstatiableObjectMethodReader {

    public static function extractPropertyNameFromGetterSetter($sGetterSetter) {
        return self::extractPropertyNameFromPrefix($sGetterSetter, ['get', 'set']);
    }

    public static function extractPropertyNameFromPrefix($sAcessorMethod, $xPrefix) {
        $aPrefix = is_array($xPrefix) ? $xPrefix : [ $xPrefix ];
        $sPrefix = implode('|', $aPrefix);
        list($sProperty) = Regex::match()->execute(RegexExpression::stringPattern($sAcessorMethod, "@(?<={$sPrefix}).*@"));
        return lcfirst($sProperty);
    }

    public static function isMethodGetterOrSetter($sMethodName) {
        return static::isMethodGetter($sMethodName)
            || static::isMethodSetter($sMethodName);
    }

    public static function isMethodGetter($sMethodName) {
        return static::isMethodByPrefix($sMethodName, 'get');
    }

    public static function isMethodSetter($sMethodName) {
        return static::isMethodByPrefix($sMethodName, 'set');
    }

    public static function isMagicMethod($sMethodName) {
        return static::isMethodByPrefix($sMethodName, '__');
    }

    public static function isMethodByPrefix($sMethodName, $sPrefix) {
        $oExpression = RegexExpression::stringPattern(
            $sMethodName,
            strtr('@{sPrefix}[\w\d]+@', [ '{sPrefix}' => $sPrefix ])
        );
        return Regex::match()->execute($oExpression);
    }
    
    public static function isMethodByExpression($sMethodName, RegexExpression $oExpression) {
        $oExpression->setTest($sMethodName);
        return Regex::match()->execute($oExpression);
    }

}