<?php

declare(strict_types=1);

namespace CaOp\Template;

use ObjectFlow\Trait\SingletonInstanceTrait;

/**
 * Provides structured telemetry data from HTTP requests for security monitoring (SIEM).
 */
final class TelemetryTemplates
{
    use SingletonInstanceTrait;

    /**
     * Extracts structured HTTP request telemetry data.
     *
     * @return array<string, mixed> Associative array with request information.
     */
    public function handleRequest(): array
    {
        $aHeaders = function_exists('getallheaders') ? getallheaders() : [];
        $sBody    = file_get_contents('php://input') ?: '';

        return [
            'timestamp'     => date('c'), // ISO 8601 for SIEM compatibility
            'method'        => $_SERVER['REQUEST_METHOD']      ?? 'N/A',
            'uri'           => $_SERVER['REQUEST_URI']         ?? 'N/A',
            'query_string'  => $_SERVER['QUERY_STRING']        ?? '',
            'protocol'      => $_SERVER['SERVER_PROTOCOL']     ?? '',
            'remote_ip'     => $_SERVER['REMOTE_ADDR']         ?? '',
            'user_agent'    => $_SERVER['HTTP_USER_AGENT']     ?? '',
            'referer'       => $_SERVER['HTTP_REFERER']        ?? '',
            'host'          => $_SERVER['HTTP_HOST']           ?? '',
            'port'          => (int) ($_SERVER['SERVER_PORT']  ?? 0),
            'content_type'  => $_SERVER['CONTENT_TYPE']        ?? '',
            'get_params'    => $this->sanitize($_GET),
            'post_params'   => $this->sanitize($_POST),
            'cookies'       => $this->sanitize($_COOKIE),
            'headers'       => $this->sanitize($aHeaders),
            'body_length'   => strlen($sBody),
        ];
    }

    /**
     * Returns a minimal safe snapshot of global variables for auditing.
     *
     * @return array<string, mixed> Filtered global information.
     */
    public function globalsInfo(): array
    {
        return [
            '_ENV'     => $this->sanitize($_ENV),
            '_FILES'   => $this->sanitize($_FILES),
            '_SERVER'  => $this->sanitize($_SERVER),
            'included_files' => get_included_files(),
        ];
    }

    /**
     * Returns session-related information, excluding sensitive values.
     *
     * @return array<string, mixed> Filtered session variables.
     */
    public function sessionInfo(): array
    {
        return isset($_SESSION) ? $this->sanitize($_SESSION) : [];
    }

    /**
     * Sanitizes an array by masking potentially sensitive values.
     *
     * @param array<string, mixed> $aData Input array.
     * @return array<string, mixed> Sanitized output.
     */
    private function sanitize(array $aData): array
    {
        $aSanitized = [];

        foreach ($aData as $sKey => $xValue) {
            if (preg_match('/pass|token|auth|secret/i', (string) $sKey)) {
                $aSanitized[$sKey] = '[MASKED]';
            } else {
                $aSanitized[$sKey] = is_array($xValue)
                    ? $this->sanitize($xValue)
                    : $xValue;
            }
        }

        return $aSanitized;
    }
}
