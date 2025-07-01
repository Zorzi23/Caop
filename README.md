# 📡 PHP Auto Custom OpenTelemetry Library

A PHP library for **automatic telemetry using [OpenTelemetry](https://opentelemetry.io/)**, enabling function and method monitoring via YAML configuration with minimal code changes.

---

## 🎞️ Installation

Install via Composer from [Packagist](https://packagist.org/packages/zorzi23/custom-auto-open-telemetry):

```bash
composer require zorzi23/custom-auto-open-telemetry
```

[View on Packagist »](https://packagist.org/packages/zorzi23/custom-auto-open-telemetry)

---

## ⚙️ Requirements

* PHP >= 8.0
* `opentelemetry` PHP extension enabled
* Required PHP extensions: `json`, `curl`, etc.
* Core dependencies:

  * `open-telemetry/opentelemetry`
  * `open-telemetry/sdk`
  * `open-telemetry/exporter-otlp`
  * `symfony/yaml`
  * `zorzi23/object_flow`

---

## 🛠️ PHP Configuration (`php.ini`)

```ini
[opentelemetry]
extension=opentelemetry.so
opentelemetry.allow_stack_extension=1

OTEL_PHP_AUTOLOAD_ENABLED=true
OTEL_SERVICE_NAME=your-service-name
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf
OTEL_EXPORTER_OTLP_ENDPOINT=http://192.168.197.33:4318
OTEL_PROPAGATORS=baggage,tracecontext
;OTEL_TRACES_EXPORTER=console ; Uncomment for local testing only
```

---

## 📅 YAML Configuration Example

The library uses YAML files to declare which functions, methods, or classes should be instrumented. The structure supports nested `children` to define span hierarchies.

Example main config (`telemetry_config.yml`):

```yaml
version: 1.0
name: 'Caop'
entities:
  - class: "CaOp\\Template\\TelemetryTemplates"
    method: "handleRequest"
    span_name: 'RequestInfo 😎'
    include:
      - file: "curl_telemetry_config.yml"
      - file: "postgres_telemetry_config.yml"
```

* The included YAML files (e.g., `curl_telemetry_config.yml`, `postgres_telemetry_config.yml`) define detailed instrumentation rules for specific libraries or domains.
* Each included file contains its own `entities` section specifying functions, span attributes, sensitive data masking, etc.

---

## 🚀 Usage Example

```php
<?php
declare(strict_types=1);
require_once('vendor/autoload.php');

function handleGlobalsInfo() {
    return $GLOBALS;
}

handleGlobalsInfo();

// Initialize cURL session
$ch = curl_init();

// Example URL (public API for testing)
$url = "https://example.com.br";

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    // Decode JSON response if applicable
    $data = json_decode($response, true);
    print_r($data);
}

// Close the cURL session
curl_close($ch);
```

---

## 🪠 Built-in Template Functions

The library provides optional utility functions under the `CaOp\Templates\TelemetryTemplates` namespace:

| Function          | Description                                     |
| ----------------- | ----------------------------------------------- |
| `globalsInfo()`   | Captures the contents of `$GLOBALS`             |
| `handleRequest()` | Captures `$_SERVER`, `$_GET`, `$_POST`, headers |
| `sessionInfo()`   | Captures `$_SESSION` if a session is active     |

To use them, include `TelemetryTemplates.php` and reference their fully qualified names in your YAML config.

---

## 🦖 Running Tests

```bash
vendor/bin/phpunit
```

Tests cover:

* Span hierarchy based on entity structure
* Attribute capture (e.g., `curl_init`)
* Error logging for invalid configurations

---

## 📋 Notes

* Missing functions or methods are logged to the PHP error log.
* YAML-driven configuration allows easy extension of trace logic without modifying business code.
* YAML configs can be dynamically loaded at runtime.

---

## 📄 License

MIT License
© Gustavo H Zorzi A Pereira