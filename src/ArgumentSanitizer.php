<?php
declare(strict_types=1);
namespace CaOp;

/**
 * Sanitizes function arguments and return values for telemetry using print_r
 */
final class ArgumentSanitizer
{

    /**
     * Sanitizes function arguments for telemetry using print_r
     *
     * @param array<mixed> $aArgs Arguments to sanitize
     * @return string Sanitized representation of arguments
     */
    public function sanitizeArguments(array $aArgs): string
    {
        return print_r($aArgs, true);
    }

    /**
     * Sanitizes function return value for telemetry using print_r
     *
     * @param mixed $xValue Value to sanitize
     * @return string Sanitized representation of the return value
     */
    public function sanitizeReturnValue(mixed $xValue): string
    {
        return print_r($xValue, true);
    }
}