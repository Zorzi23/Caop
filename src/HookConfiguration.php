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
    private readonly ArgumentSanitizer $oArgumentSanitizer;
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
     * @param ArgumentSanitizer $oArgumentSanitizer Argument sanitizer
     */
    private function __construct(
        string $sSpanName,
        int $iSpanKind,
        bool $bCaptureArgs,
        bool $bCaptureReturn,
        bool $bCaptureErrors,
        CachedInstrumentation $oInstrumentation,
        ArgumentSanitizer $oArgumentSanitizer
    ) {
        $this->sSpanName = $sSpanName;
        $this->iSpanKind = $iSpanKind;
        $this->bCaptureArgs = $bCaptureArgs;
        $this->bCaptureReturn = $bCaptureReturn;
        $this->bCaptureErrors = $bCaptureErrors;
        $this->oInstrumentation = $oInstrumentation;
        $this->oArgumentSanitizer = $oArgumentSanitizer;
    }

    /**
     * Creates an instance with default configuration
     *
     * @param string $sFunctionName Function name for the span
     * @param CachedInstrumentation $oInstrumentation Instrumentation instance
     * @param ArgumentSanitizer $oArgumentSanitizer Argument sanitizer
     * @return self
     */
    public static function createWithDefaults(
        string $sFunctionName,
        CachedInstrumentation $oInstrumentation,
        ArgumentSanitizer $oArgumentSanitizer
    ): self {
        return new self(
            $sFunctionName,
            SpanKind::KIND_INTERNAL,
            true,
            false,
            true,
            $oInstrumentation,
            $oArgumentSanitizer
        );
    }

    /**
     * Creates an instance from configuration options
     *
     * @param array<string, mixed> $aOptions Configuration options
     * @param string $sFunctionName Fallback function name for the span
     * @param CachedInstrumentation $oInstrumentation Instrumentation instance
     * @param ArgumentSanitizer $oArgumentSanitizer Argument sanitizer
     * @return self
     */
    public static function createFromOptions(
        array $aOptions,
        string $sFunctionName,
        CachedInstrumentation $oInstrumentation,
        ArgumentSanitizer $oArgumentSanitizer
    ): self {
        return new self(
            $aOptions['span_name'] ?? $sFunctionName ?: self::DEFAULT_SPAN_NAME,
            $aOptions['span_kind'] ?? SpanKind::KIND_INTERNAL,
            $aOptions['capture_args'] ?? true,
            $aOptions['capture_return'] ?? false,
            $aOptions['capture_errors'] ?? true,
            $oInstrumentation,
            $oArgumentSanitizer
        );
    }

    /**
     * Handles pre-execution hook for tracing
     *
     * @param array<mixed> $aArgs Arguments to trace
     * @return array<mixed> Original arguments
     */
    public function handlePreHook(array $aArgs): array
    {
        $oSpanBuilder = $this->oInstrumentation->tracer()
            ->spanBuilder($this->sSpanName)
            ->setSpanKind($this->iSpanKind);
        $oSpan = $oSpanBuilder->startSpan();
        
        if ($this->bCaptureArgs) {
            $oSpan->setAttribute(
                "{$this->sSpanName}.args",
                $this->oArgumentSanitizer->sanitizeArguments($aArgs)
            );
        }
        
        Context::storage()->attach($oSpan->storeInContext(Context::getCurrent()));
        return $aArgs;
    }

    /**
     * Handles post-execution hook for tracing
     *
     * @param mixed $xResult Execution result
     * @param array<mixed> $aParams Parameters
     * @param ScopeInterface|null $oScope Tracing scope
     * @param Throwable|null $oException Caught exception
     * @return mixed Original result
     */
    public function handlePostHook(){
        $aArgs = func_get_args();
        $oScope = Context::storage()->scope();
        $oScope->detach();
        $oSpan = Span::fromContext($oScope->context());
        // if ($exception) {
        //     $span->recordException($exception);
        //     $span->setStatus(StatusCode::STATUS_ERROR);
        // }
        $oSpan->end();
        // if (!$oScope) {
        //     return $xResult;
        // }

        // try {
        //     $oSpan = Span::getCurrent();

        //     if ($this->bCaptureReturn) {
        //         $oSpan->setAttribute(
        //             "{$this->sSpanName}.return",
        //             $this->oArgumentSanitizer->sanitizeReturnValue($xResult)
        //         );
        //     }

        //     if ($this->bCaptureErrors && $oException) {
        //         $oSpan->recordException($oException);
        //         $oSpan->setStatus(StatusCode::STATUS_ERROR, $oException->getMessage());
        //     }
        // } finally {
        //     $oSpan->end();
        //     $oScope->detach();
        // }

        // return $xResult;
    }
}