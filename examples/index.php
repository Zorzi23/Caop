<?php
declare(strict_types=1);
require_once(dirname(__DIR__).'/vendor/autoload.php');

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