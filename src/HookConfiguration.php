<?php
declare(strict_types=1);

namespace CaOp;

use ObjectFlow\Trait\CacheableInstanceTrait;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\Context\Context;
use Throwable;

/**
 * Configures telemetry hooks for tracing entity execution
 */
final class HookConfiguration
{
    use CacheableInstanceTrait;

    private const DEFAULT_SPAN_NAME = 'default_span';

    private readonly string $sSpanName;
    private readonly int $iSpanKind;
    private readonly bool $bCaptureArgs;
    private readonly bool $bCaptureReturn;
    private readonly bool $bCaptureErrors;
    private readonly CachedInstrumentation $oInstrumentation;
    private readonly AttributesCollector $oAttributesCollector;
    private readonly ?string $sParentIdentifier;
    private readonly EntityTelemetry $oTelemetry;
    private static ?Span $oRequestSpan = null;

    /**
     * Private constructor to enforce factory method usage
     *
     * @param string $sSpanName Name of the span
     * @param int $iSpanKind Span kind (e.g., SpanKind::KIND_INTERNAL)
     * @param bool $bCaptureArgs Whether to capture arguments
     * @param bool $bCaptureReturn Whether to capture return values
     * @param bool $bCaptureErrors Whether to capture errors
     * @param CachedInstrumentation $oInstrumentation Instrumentation instance
     * @param AttributesCollector $oAttributesCollector Attribute collector
     * @param string|null $sParentIdentifier Parent entity identifier
     * @param EntityTelemetry $oTelemetry Telemetry instance for span registry
     */
    private function __construct(
        string $sSpanName,
        int $iSpanKind,
        bool $bCaptureArgs,
        bool $bCaptureReturn,
        bool $bCaptureErrors,
        CachedInstrumentation $oInstrumentation,
        AttributesCollector $oAttributesCollector,
        ?string $sParentIdentifier,
        EntityTelemetry $oTelemetry
    ) {
        $this->sSpanName = $sSpanName;
        $this->iSpanKind = $iSpanKind;
        $this->bCaptureArgs = $bCaptureArgs;
        $this->bCaptureReturn = $bCaptureReturn;
        $this->bCaptureErrors = $bCaptureErrors;
        $this->oInstrumentation = $oInstrumentation;
        $this->oAttributesCollector = $oAttributesCollector;
        $this->sParentIdentifier = $sParentIdentifier;
        $this->oTelemetry = $oTelemetry;
    }

    /**
     * Creates an instance with default configuration
     *
     * @param string $sFunctionName Function name for the span
     * @param CachedInstrumentation $oInstrumentation Instrumentation instance
     * @param AttributesCollector $oAttributesCollector Attribute collector
     * @param EntityTelemetry $oTelemetry Telemetry instance
     * @return self
     */
    public static function createWithDefaults(
        string $sFunctionName,
        CachedInstrumentation $oInstrumentation,
        AttributesCollector $oAttributesCollector,
        EntityTelemetry $oTelemetry
    ): self {
        return new self(
            $sFunctionName,
            SpanKind::KIND_INTERNAL,
            true,
            false,
            true,
            $oInstrumentation,
            $oAttributesCollector,
            null,
            $oTelemetry
        );
    }

    /**
     * Creates an instance from configuration options
     *
     * @param array<string, mixed> $aOptions Configuration options
     * @param string $sFunctionName Fallback function name for the span
     * @param CachedInstrumentation $oInstrumentation Instrumentation instance
     * @param AttributesCollector $oAttributesCollector Attribute collector
     * @param string|null $sParentIdentifier Parent entity identifier
     * @param EntityTelemetry $oTelemetry Telemetry instance
     * @return self
     */
    public static function createFromOptions(
        array $aOptions,
        string $sFunctionName,
        CachedInstrumentation $oInstrumentation,
        AttributesCollector $oAttributesCollector,
        ?string $sParentIdentifier,
        EntityTelemetry $oTelemetry
    ): self {
        return new self(
            $aOptions['span_name'] ?? $sFunctionName ?: self::DEFAULT_SPAN_NAME,
            $aOptions['span_kind'] ?? SpanKind::KIND_INTERNAL,
            $aOptions['capture_args'] ?? true,
            $aOptions['capture_return'] ?? true,
            $aOptions['capture_errors'] ?? true,
            $oInstrumentation,
            $oAttributesCollector,
            $sParentIdentifier,
            $oTelemetry
        );
    }

    /**
     * Handles pre-execution hook for tracing
     *
     * @param array<mixed> $aArgs Arguments to trace ([$xObject, $aParams] or direct params)
     * @return array<mixed> Original parameters
     */
    public function handlePreHook(array $aArgs): array
    {
        $oSpanBuilder = $this->oInstrumentation->tracer()
            ->spanBuilder($this->sSpanName)
            ->setSpanKind($this->iSpanKind);

        // Set parent span context if specified
        if ($this->sParentIdentifier) {
            $oParentContext = $this->oTelemetry->getSpanContext($this->sParentIdentifier);
            if ($oParentContext) {
                $oSpanBuilder->setParent($oParentContext);
            } else {
                error_log("HookConfiguration: Parent context {$this->sParentIdentifier} not found for {$this->sSpanName}");
            }
        }

        $oSpan = $oSpanBuilder->startSpan();
        $this->oTelemetry->storeSpan($this->sSpanName, $oSpan);

        if ($this->bCaptureArgs) {
            // Handle [$xObject, $aParams] from EntityHookRegister or direct params
            $aEntityArgs = count($aArgs) === 2 && is_array($aArgs[1]) ? $aArgs[1] : array_values($aArgs)[0] ?? [];
            $this->oAttributesCollector->addFromMixed($oSpan, "{$this->sSpanName}.args", $aEntityArgs);
        }

        Context::storage()->attach($oSpan->storeInContext(Context::getCurrent()));
        return count($aArgs) === 2 && is_array($aArgs[1]) ? $aArgs[1] : $aArgs;
    }

    /**
     * Handles post-execution hook for tracing
     *
     * @param mixed $xObject Object instance (if method)
     * @param array<mixed> $aParams Parameters
     * @param mixed $xResult Execution result
     * @param Throwable|null $oException Caught exception
     * @return mixed Original result
     */
    public function handlePostHook(
        mixed $xObject,
        array $aParams,
        mixed $xResult,
        ?Throwable $oException
    ): mixed {
        $oScope = Context::storage()->scope();
        if (!$oScope) {
            error_log("HookConfiguration: Unknown scope on post hook for {$this->sSpanName}");
            return $xResult;
        }

        try {
            $oSpan = Span::fromContext($oScope->context());
            if ($this->bCaptureReturn) {
                $this->oAttributesCollector->addFromMixed($oSpan, "{$this->sSpanName}.return", $xResult);
            }
            if ($this->bCaptureErrors && $oException !== null) {
                $oSpan->recordException($oException);
                $oSpan->setStatus(StatusCode::STATUS_ERROR, $oException->getMessage());
            }
        } finally {
            $oSpan->end();
            $oScope->detach();
        }

        return $xResult;
    }
}