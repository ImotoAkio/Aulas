<?php
// Arquivo de teste para verificar se as funções estão funcionando
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testando funções...\n";

try {
    require_once __DIR__ . '/config/database.php';
    
    echo "✅ Arquivo config/database.php carregado com sucesso\n";
    
    if (function_exists('getBaseUrl')) {
        echo "✅ Função getBaseUrl() existe\n";
        $baseUrl = getBaseUrl();
        echo "Base URL: " . $baseUrl . "\n";
    } else {
        echo "❌ Função getBaseUrl() não encontrada\n";
    }
    
    if (function_exists('getAssetUrl')) {
        echo "✅ Função getAssetUrl() existe\n";
        $assetUrl = getAssetUrl('assets/css/style.css');
        echo "Asset URL: " . $assetUrl . "\n";
    } else {
        echo "❌ Função getAssetUrl() não encontrada\n";
    }
    
    if (function_exists('getPageUrl')) {
        echo "✅ Função getPageUrl() existe\n";
        $pageUrl = getPageUrl('login.php');
        echo "Page URL: " . $pageUrl . "\n";
    } else {
        echo "❌ Função getPageUrl() não encontrada\n";
    }
    
    if (isset($pdo)) {
        echo "✅ Conexão PDO estabelecida\n";
    } else {
        echo "❌ Conexão PDO não estabelecida\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
