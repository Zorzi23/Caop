<?php

declare(strict_types=1);

namespace CaOp;

use CaOp\Security\SymmetricMasker;
use ObjectFlow\Trait\CacheableInstanceTrait;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\SpanInterface;
use Throwable;

/**
 * Configures telemetry hooks for tracing entity execution with customizable span attributes and sensitive data masking.
 *
 * @package CaOp
 */
final class HookConfiguration
{
    use CacheableInstanceTrait;

    private const DEFAULT_SPAN_NAME = 'default_span';
    private const PEPPER = 'fixed_pepper_value_123456';

    /**
     * @var string $sSpanName The name of the span
     */
    private readonly string $sSpanName;

    /**
     * @var int $iSpanKind The kind of the span (e.g., SpanKind::KIND_INTERNAL)
     */
    private readonly int $iSpanKind;

    /**
     * @var bool $bCaptureArgs Whether to capture function/method arguments
     */
    private readonly bool $bCaptureArgs;

    /**
     * @var bool $bCaptureReturn Whether to capture function/method return value
     */
    private readonly bool $bCaptureReturn;

    /**
     * @var bool $bCaptureErrors Whether to capture errors/exceptions
     */
    private readonly bool $bCaptureErrors;

    /**
     * @var array<string, mixed> $aSpanAttributes Custom attributes to apply to the span
     */
    private readonly array $aSpanAttributes;

    /**
     * @var array<int> $aSensitiveParams List of parameter indices to mask
     */
    private readonly array $aSensitiveParams;

    /**
     * @var bool $bSensitiveReturn Whether to mask the return value
     */
    private readonly bool $bSensitiveReturn;

    /**
     * @var CachedInstrumentation $oInstrumentation The OpenTelemetry instrumentation instance
     */
    private readonly CachedInstrumentation $oInstrumentation;

    /**
     * @var AttributesCollector $oAttributesCollector The attributes collector for spans
     */
    private readonly AttributesCollector $oAttributesCollector;

    /**
     * @var string|null $sParentIdentifier The parent entity identifier, if any
     */
    private readonly ?string $sParentIdentifier;

    /**
     * @var EntityTelemetry $oTelemetry The telemetry instance
     */
    private readonly EntityTelemetry $oTelemetry;

    /**
     * Private constructor to enforce static creation.
     *
     * @param string              $sSpanName The name of the span
     * @param int                 $iSpanKind The kind of the span
     * @param bool                $bCaptureArgs Whether to capture arguments
     * @param bool                $bCaptureReturn Whether to capture return value
     * @param bool                $bCaptureErrors Whether to capture errors
     * @param array<string, mixed> $aSpanAttributes Custom attributes for the span
     * @param array<int>          $aSensitiveParams List of parameter indices to mask
     * @param bool                $bSensitiveReturn Whether to mask the return value
     * @param CachedInstrumentation $oInstrumentation The instrumentation instance
     * @param AttributesCollector $oAttributesCollector The attributes collector
     * @param string|null         $sParentIdentifier The parent entity identifier
     * @param EntityTelemetry     $oTelemetry The telemetry instance
     */
    private function __construct(
        string $sSpanName,
        int $iSpanKind,
        bool $bCaptureArgs,
        bool $bCaptureReturn,
        bool $bCaptureErrors,
        array $aSpanAttributes,
        array $aSensitiveParams,
        bool $bSensitiveReturn,
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
        $this->aSpanAttributes = $aSpanAttributes;
        $this->aSensitiveParams = $aSensitiveParams;
        $this->bSensitiveReturn = $bSensitiveReturn;
        $this->oInstrumentation = $oInstrumentation;
        $this->oAttributesCollector = $oAttributesCollector;
        $this->sParentIdentifier = $sParentIdentifier;
        $this->oTelemetry = $oTelemetry;
    }

    /**
     * Creates a hook configuration with default settings.
     *
     * @param string              $sFunctionName The function or method name
     * @param CachedInstrumentation $oInstrumentation The instrumentation instance
     * @param AttributesCollector $oAttributesCollector The attributes collector
     * @param EntityTelemetry     $oTelemetry The telemetry instance
     * @return self
     */
    public static function createWithDefaults(
        string $sFunctionName,
        CachedInstrumentation $oInstrumentation,
        AttributesCollector $oAttributesCollector,
        EntityTelemetry $oTelemetry
    ): self {
        return new self(
            sSpanName: $sFunctionName,
            iSpanKind: SpanKind::KIND_INTERNAL,
            bCaptureArgs: true,
            bCaptureReturn: false,
            bCaptureErrors: true,
            aSpanAttributes: [],
            aSensitiveParams: [],
            bSensitiveReturn: false,
            oInstrumentation: $oInstrumentation,
            oAttributesCollector: $oAttributesCollector,
            sParentIdentifier: null,
            oTelemetry: $oTelemetry
        );
    }

    /**
     * Creates a hook configuration from provided options.
     *
     * @param array<string, mixed>  $aOptions Configuration options
     * @param string                $sFunctionName The function or method name
     * @param CachedInstrumentation $oInstrumentation The instrumentation instance
     * @param AttributesCollector   $oAttributesCollector The attributes collector
     * @param string|null           $sParentIdentifier The parent entity identifier
     * @param EntityTelemetry       $oTelemetry The telemetry instance
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
            sSpanName: $aOptions['span_name'] ?? $sFunctionName ?: self::DEFAULT_SPAN_NAME,
            iSpanKind: $aOptions['span_kind'] ?? SpanKind::KIND_INTERNAL,
            bCaptureArgs: $aOptions['capture_args'] ?? true,
            bCaptureReturn: $aOptions['capture_return'] ?? true,
            bCaptureErrors: $aOptions['capture_errors'] ?? true,
            aSpanAttributes: $aOptions['attributes'] ?? [],
            aSensitiveParams: $aOptions['sensitive_params'] ?? [],
            bSensitiveReturn: $aOptions['sensitive_return'] ?? false,
            oInstrumentation: $oInstrumentation,
            oAttributesCollector: $oAttributesCollector,
            sParentIdentifier: $sParentIdentifier,
            oTelemetry: $oTelemetry
        );
    }

    /**
     * Masks sensitive data using HMAC with salt and pepper.
     *
     * @param string $sValue The value to mask
     * @return string The masked value
     */
    private function maskSensitiveData(string $sValue): string
    {
        return SymmetricMasker::newInstance()->mask($sValue);
    }

    /**
     * Handles logic before the entity execution, applying custom attributes and masking sensitive parameters.
     *
     * @param array<mixed> $aArgs The arguments passed to the function/method
     * @return array<mixed> The arguments to be passed to the function/method
     */
    public function handlePreHook(array $aArgs): array
    {
        $oSpanBuilder = $this->oInstrumentation
            ->tracer()
            ->spanBuilder($this->sSpanName)
            ->setSpanKind($this->iSpanKind);

        $this->applyParentContextIfExists($oSpanBuilder);

        if (!empty($this->aSpanAttributes)) {
            $oSpanBuilder->setAttributes($this->aSpanAttributes);
        }

        $oSpan = $oSpanBuilder->startSpan();
        $this->oTelemetry->storeSpan($this->sSpanName, $oSpan);

        if ($this->bCaptureArgs) {
            $aEntityParams = $this->extractArgs($aArgs);
            if (!empty($this->aSensitiveParams) && is_array($aEntityParams)) {
                foreach ($this->aSensitiveParams as $iIndex) {
                    if (isset($aEntityParams[$iIndex]) && is_string($aEntityParams[$iIndex])) {
                        $aEntityParams[$iIndex] = $this->maskSensitiveData($aEntityParams[$iIndex]);
                    }
                }
            }
            $this->oAttributesCollector->addFromMixed($oSpan, "{$this->sSpanName}.args", $aEntityParams);
        }

        Context::storage()->attach($oSpan->storeInContext(Context::getCurrent()));
        return $this->returnOriginalArgs($aArgs);
    }

    /**
     * Handles logic after the entity execution, masking sensitive return values.
     *
     * @param mixed            $xObject The object instance (if method call)
     * @param array<mixed>     $aParams The parameters passed to the function/method
     * @param mixed            $xResult The return value of the function/method
     * @param Throwable|null   $oException The exception thrown, if any
     * @return mixed The original return value
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
            $oSpan->setStatus(StatusCode::STATUS_OK);
            if ($this->bCaptureReturn) {
                $xMaskedResult = $this->bSensitiveReturn && is_string($xResult)
                    ? $this->maskSensitiveData($xResult)
                    : $xResult;
                $this->oAttributesCollector->addFromMixed($oSpan, "{$this->sSpanName}.return", $xMaskedResult);
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

    /**
     * Applies parent context to the span builder if available.
     *
     * @param SpanBuilderInterface $oSpanBuilder The span builder instance
     * @return void
     */
    private function applyParentContextIfExists(SpanBuilderInterface $oSpanBuilder): void
    {
        if (!$this->sParentIdentifier) {
            return;
        }

        $oParentContext = $this->oTelemetry->getSpanContext($this->sParentIdentifier);

        if ($oParentContext) {
            $oSpanBuilder->setParent($oParentContext);
        } else {
            error_log("HookConfiguration: Parent context {$this->sParentIdentifier} not found for {$this->sSpanName}");
        }
    }

    /**
     * Extracts arguments from the pre-hook payload.
     *
     * @param array<mixed> $aArgs The arguments passed to the function/method
     * @return mixed The extracted arguments
     */
    private function extractArgs(array $aArgs): mixed
    {
        return $aArgs[1] ?? [];
        // return count($aArgs) === 2 && is_array($aArgs[1])
        //     ? $aArgs[1]
        //     : array_values($aArgs)[0] ?? [];
    }

    /**
     * Returns the original parameters to pass to the function/method.
     *
     * @param array<mixed> $aArgs The arguments passed to the function/method
     * @return array<mixed> The original arguments
     */
    private function returnOriginalArgs(array $aArgs): array
    {
        return count($aArgs) === 2 && is_array($aArgs[1]) ? $aArgs[1] : $aArgs;
    }
}