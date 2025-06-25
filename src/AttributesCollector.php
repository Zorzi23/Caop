<?php

declare(strict_types=1);

namespace CaOp;

use OpenTelemetry\API\Trace\SpanInterface;

/**
 * Collects and attaches structured arguments as attributes to telemetry spans.
 */
final class AttributesCollector
{

    /**
     * Automatically handles any type of data (scalar, array, object) and sets it as span attributes.
     *
     * @param SpanInterface $oSpan Target span
     * @param string $sKey Base attribute key
     * @param mixed $xValue Value to normalize and attach
     * @return void
     */
    public function addFromMixed(SpanInterface $oSpan, string $sKey, mixed $xValue): void
    {
        if (is_array($xValue)) {
            $this->addFromArray($oSpan, $sKey, $xValue);
        } elseif (is_scalar($xValue) || $xValue === null) {
            $oSpan->setAttribute($sKey, $xValue);
        } elseif (is_object($xValue)) {
            if (method_exists($xValue, '__toString')) {
                $oSpan->setAttribute($sKey, (string) $xValue);
            } else {
                $oSpan->setAttribute("{$sKey}.json", json_encode($xValue));
                $oSpan->setAttribute("{$sKey}.class", get_class($xValue));
            }
        } elseif (is_resource($xValue)) {
            $oSpan->setAttribute($sKey, 'resource');
        } else {
            $oSpan->setAttribute($sKey, json_encode($xValue));
        }
    }

    /**
     * Attaches a raw array (e.g. $_SERVER) to the span with optional prefix.
     *
     * @param SpanInterface $oSpan Target span to receive the attributes
     * @param string $sPrefix Prefix for all keys (e.g., "request.server")
     * @param array<string, mixed> $aData The array data to attach
     * @return void
     */
    public function addFromArray(SpanInterface $oSpan, string $sPrefix, array $aData): void
    {
        $this->flattenAttributes($oSpan, $sPrefix, $aData);
    }

    /**
     * Attaches arguments as a single nested attribute.
     *
     * @param SpanInterface $oSpan
     * @param string $sArgumentName
     * @param array<string, mixed>|null $aArgs
     * @return void
     */
    public function addNestedArgs(SpanInterface $oSpan, string $sArgumentName, ?array $aArgs): void
    {
        $oSpan->setAttribute($sArgumentName, $aArgs);
    }

    /**
     * Attaches arguments as flattened attributes using dot notation.
     *
     * @param SpanInterface $oSpan
     * @param string $sPrefix
     * @param array<string, mixed> $aArgs
     * @return void
     */
    public function addFlattenArgs(SpanInterface $oSpan, string $sPrefix, array $aArgs): void
    {
        $this->flattenAttributes($oSpan, $sPrefix, $aArgs);
    }

    /**
     * Encodes arguments as JSON string and sets it on the span.
     *
     * @param SpanInterface $oSpan
     * @param string $sArgumentName
     * @param array<string, mixed> $aArgs
     * @return void
     */
    public function addJsonArgs(SpanInterface $oSpan, string $sArgumentName, array $aArgs): void
    {
        $oSpan->setAttribute("{$sArgumentName}.json", json_encode($aArgs));
    }

    /**
     * Recursively flattens and attaches array data as dot-notation span attributes.
     *
     * @param SpanInterface $oSpan
     * @param string $sPrefix
     * @param array<string, mixed> $aData
     * @return void
     */
    private function flattenAttributes(SpanInterface $oSpan, string $sPrefix, array $aData): void
    {
        foreach ($aData as $sKey => $xValue) {
            $sAttrName = "{$sPrefix}.{$sKey}";

            if (is_array($xValue)) {
                $this->flattenAttributes($oSpan, $sAttrName, $xValue);
            } else {
                $oSpan->setAttribute($sAttrName, $this->normalizeValue($xValue));
            }
        }
    }

    /**
     * Normalizes a value to a scalar type supported by Span attributes.
     *
     * @param mixed $xValue
     * @return string|int|bool|null
     */
    private function normalizeValue(mixed $xValue): mixed
    {
        return match (true) {
            is_scalar($xValue), is_null($xValue) => $xValue,
            is_object($xValue) => method_exists($xValue, '__toString')
                ? (string) $xValue
                : get_class($xValue),
            is_resource($xValue) => 'resource',
            default => json_encode($xValue),
        };
    }
}
