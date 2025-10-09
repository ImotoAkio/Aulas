<?php
/**
 * DIAGNÓSTICO DE HOSPEDAGEM
 * Identifica problemas específicos do ambiente de produção
 */

echo "<h2>🔍 DIAGNÓSTICO DE HOSPEDAGEM</h2>";
echo "<p>Verificando configurações e possíveis problemas...</p>";

echo "<h3>📋 Informações do Servidor</h3>";
echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #e0e0e0;'>";
echo "<th style='padding: 8px;'>Configuração</th>";
echo "<th style='padding: 8px;'>Valor</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "</tr>";

$configuracoes = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time'),
    'Upload Max Filesize' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
    'Error Reporting' => error_reporting(),
    'Display Errors' => ini_get('display_errors') ? 'ON' : 'OFF',
    'Log Errors' => ini_get('log_errors') ? 'ON' : 'OFF',
    'Timezone' => date_default_timezone_get()
];

foreach ($configuracoes as $nome => $valor) {
    $status = '✅ OK';
    if ($nome === 'PHP Version' && version_compare($valor, '7.4', '<')) {
        $status = '⚠️ Versão antiga';
    }
    if ($nome === 'Memory Limit' && intval($valor) < 128) {
        $status = '⚠️ Baixo limite';
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$nome</strong></td>";
    echo "<td style='padding: 8px;'>$valor</td>";
    echo "<td style='padding: 8px;'>$status</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<h3>📁 Verificação de Arquivos</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";

$arquivos_necessarios = [
    'config/database.php',
    'index.php',
    'login.php'
];

echo "<h4>Arquivos Necessários:</h4>";
echo "<ul>";
foreach ($arquivos_necessarios as $arquivo) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        $permissao = substr(sprintf('%o', fileperms($arquivo)), -4);
        echo "<li style='color: green;'>✅ $arquivo (Tamanho: {$tamanho} bytes, Permissão: $permissao)</li>";
    } else {
        echo "<li style='color: red;'>❌ $arquivo (NÃO ENCONTRADO)</li>";
    }
}
echo "</ul>";
echo "</div>";

echo "<h3>🔌 Teste de Conexão com Banco</h3>";
echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px;'>";

if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        echo "✅ Arquivo config/database.php carregado<br>";
        
        if (function_exists('getConnection')) {
            echo "✅ Função getConnection() encontrada<br>";
            
            try {
                $pdo = getConnection();
                echo "✅ Conexão com banco estabelecida<br>";
                
                // Testar uma query simples
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                if ($result && $result['test'] == 1) {
                    echo "✅ Query de teste executada com sucesso<br>";
                } else {
                    echo "⚠️ Query de teste falhou<br>";
                }
                
            } catch (Exception $e) {
                echo "❌ Erro de conexão: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Função getConnection() não encontrada<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao carregar config/database.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Arquivo config/database.php não encontrado<br>";
}
echo "</div>";

echo "<h3>📊 Verificação de Tabelas Existentes</h3>";
echo "<div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px;'>";

if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        $pdo = getConnection();
        
        $stmt = $pdo->query("SHOW TABLES");
        $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($tabelas) {
            echo "<h4>Tabelas Existentes:</h4>";
            echo "<ul>";
            foreach ($tabelas as $tabela) {
                echo "<li>✅ $tabela</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>⚠️ Nenhuma tabela encontrada no banco</p>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro ao verificar tabelas: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Não foi possível verificar tabelas - arquivo de configuração não encontrado<br>";
}
echo "</div>";

echo "<h3>🚨 Possíveis Problemas e Soluções</h3>";
echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";

$problemas = [];

// Verificar versão do PHP
if (version_compare(phpversion(), '7.4', '<')) {
    $problemas[] = "⚠️ <strong>PHP Version:</strong> Versão muito antiga. Recomendado: PHP 7.4+";
}

// Verificar limite de memória
if (intval(ini_get('memory_limit')) < 128) {
    $problemas[] = "⚠️ <strong>Memory Limit:</strong> Limite muito baixo. Recomendado: 128M+";
}

// Verificar se arquivos existem
if (!file_exists('config/database.php')) {
    $problemas[] = "❌ <strong>Arquivo de Configuração:</strong> config/database.php não encontrado";
}

// Verificar permissões
if (file_exists('config/database.php') && !is_readable('config/database.php')) {
    $problemas[] = "❌ <strong>Permissões:</strong> config/database.php não é legível";
}

if (empty($problemas)) {
    echo "<p style='color: green;'>✅ <strong>Nenhum problema detectado!</strong></p>";
    echo "<p>O ambiente parece estar configurado corretamente.</p>";
} else {
    echo "<h4>Problemas Detectados:</h4>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li>$problema</li>";
    }
    echo "</ul>";
    
    echo "<h4>Soluções:</h4>";
    echo "<ol>";
    echo "<li><strong>Para problemas de PHP:</strong> Entre em contato com o suporte da hospedagem</li>";
    echo "<li><strong>Para arquivos não encontrados:</strong> Verifique se fez upload correto</li>";
    echo "<li><strong>Para permissões:</strong> Ajuste via painel de controle da hospedagem</li>";
    echo "<li><strong>Para conexão com banco:</strong> Verifique configurações em config/database.php</li>";
    echo "</ol>";
}
echo "</div>";

echo "<br><div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4>📞 Próximos Passos:</h4>";
echo "<ol>";
echo "<li>Se houver problemas, corrija-os antes de executar a migração</li>";
echo "<li>Use o script <code>migrar_banco_robusto.php</code> para migração</li>";
echo "<li>Em caso de dúvidas, entre em contato com o suporte da hospedagem</li>";
echo "</ol>";
echo "</div>";

echo "<br><div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>📋 Informações para Suporte:</h4>";
echo "<p>Se precisar de ajuda, forneça estas informações:</p>";
echo "<ul>";
echo "<li>Resultado deste diagnóstico</li>";
echo "<li>Mensagens de erro específicas</li>";
echo "<li>URL da hospedagem</li>";
echo "<li>Tipo de hospedagem (compartilhada, VPS, dedicada)</li>";
echo "</ul>";
echo "</div>";
?>
