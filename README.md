# PHP Auto Custom OpenTelemetry Library

A PHP library for automatic telemetry using [OpenTelemetry](https://opentelemetry.io/), enabling function and method monitoring via YAML configuration with minimal code changes.

---

## 🎞️ Installation

```bash
composer require zorzi23/custom-auto-open-telemetry
```

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

## 📁 PHP Configuration (`php.ini`)

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

```yaml
version: 1.0
name: 'Caop'
entities:
  functions:
    - name: "handleGlobalsInfo"
      children:
        - name: curl_exec
    - class: "MyApp\MyClass"
      method: "myMethod"
```

The structure supports nested `children` to define span hierarchies.

---

## 🚀 Usage

```php
<?php
declare(strict_types=1);
require_once('vendor/autoload.php');

function handleGlobalsInfo() {
    return $GLOBALS;
}

handleGlobalsInfo();

// Inicializa o cURL
$ch = curl_init();

// URL de exemplo (API pública para teste)
$url = "https://example.com.br";

// Configura as opções do cURL
curl_setopt($ch, CURLOPT_URL, $url);          // Define a URL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string

// Executa a requisição
$response = curl_exec($ch);

// Verifica se houve erro
if (curl_errno($ch)) {
    echo 'Erro cURL: ' . curl_error($ch);
} else {
    // Decodifica a resposta JSON (se aplicável)
    $data = json_decode($response, true);
    print_r($data); // Exibe os dados
}

// Fecha a sessão cURL
curl_close($ch);
```

---

## 🪠 Built-in Template Functions

The library provides optional utility functions under `CaOp\Templates\TelemetryTemplates`:

| Function          | Description                                     |
| ----------------- | ----------------------------------------------- |
| `globalsInfo()`   | Captures the contents of `$GLOBALS`             |
| `handleRequest()` | Captures `$_SERVER`, `$_GET`, `$_POST`, headers |
| `sessionInfo()`   | Captures `$_SESSION` if a session is active     |

To use them, simply include the `TelemetryTemplates.php` file and reference their fully qualified names in your YAML config.

---

## 🦖 Running Tests

```bash
vendor/bin/phpunit
```

All components are tested using PHPUnit. Example test coverage includes:

* Span hierarchy based on entity structure
* Attribute capture (e.g., `curl_init`)
* Error logging for invalid configurations

---

## 📋 Notes

* Missing functions or methods are logged to the PHP error log.
* The YAML-driven configuration allows easy extension of trace logic without modifying business code.
* You can dynamically load YAML configs at runtime.

---

## 📄 License

MIT License
© Gustavo H Zorzi A Pereira
