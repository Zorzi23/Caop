<?php
declare(strict_types=1);
namespace CaOp;

use NestedAttributesCollector;
use OpenTelemetry\SDK\Common\Attribute\Attributes;

/**
 * Sanitizes function arguments and return values for telemetry using print_r
 * @deprecated message
 */
final class ArgumentSanitizer
{

    /**
     * Sanitizes function arguments for telemetry using print_r
     *
     * @param array<mixed> $aArgs Arguments to sanitize
     * @return string Sanitized representation of arguments
     */
    public function sanitizeArguments(array $aArgs): mixed
    {
        return print_r($aArgs, true);
    }

    /**
     * Sanitizes function return value for telemetry using print_r
     *
     * @param mixed $xValue Value to sanitize
     * @return string Sanitized representation of the return value
     */
    public function sanitizeReturnValue(mixed $xValue): mixed
    {
        return $xValue;
        // return print_r($xValue, true);
    }
}