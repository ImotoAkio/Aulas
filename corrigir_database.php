<?php
/**
 * CORRETOR DO ARQUIVO DATABASE.PHP
 * Corrige problemas no arquivo de configuração
 */

echo "<h2>🔧 CORRETOR DO ARQUIVO DATABASE.PHP</h2>";

$arquivo_config = 'config/database.php';

if (!file_exists($arquivo_config)) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO:</strong> Arquivo $arquivo_config não encontrado!<br>";
    echo "Crie o arquivo ou verifique o caminho.";
    echo "</div>";
    exit;
}

echo "<h3>📋 Verificando arquivo atual...</h3>";

// Ler conteúdo atual
$conteudo_atual = file_get_contents($arquivo_config);
echo "<p>✅ Arquivo lido com sucesso (" . strlen($conteudo_atual) . " bytes)</p>";

// Verificar se função getConnection existe
if (strpos($conteudo_atual, 'function getConnection()') === false) {
    echo "<p style='color: orange;'>⚠️ Função getConnection() não encontrada no arquivo</p>";
    
    // Criar versão corrigida
    $conteudo_corrigido = '<?php
/**
 * Arquivo de configuração centralizada para conexão com banco de dados
 * 
 * Para facilitar o deploy, altere apenas as configurações abaixo:
 */

// Configurações do banco de dados
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'u894209272_planos_aula\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');
define(\'DB_CHARSET\', \'utf8\');

// Configurações PDO
define(\'PDO_ERRMODE\', PDO::ERRMODE_EXCEPTION);
define(\'PDO_FETCH_MODE\', PDO::FETCH_ASSOC);

// Função para detectar ambiente e corrigir caminhos
function getBaseUrl() {
    if (!isset($_SERVER[\'HTTP_HOST\'])) {
        return \'/\';
    }
    
    $protocol = isset($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] === \'on\' ? \'https\' : \'http\';
    $host = $_SERVER[\'HTTP_HOST\'];
    
    // Detecta se está em produção
    $isProduction = strpos($host, \'colegiorosadesharom.com.br\') !== false;
    
    if ($isProduction) {
        return $protocol . \'://\' . $host . \'/\';
    } else {
        return $protocol . \'://\' . $host . \'/aulas/\';
    }
}

// Função para gerar URL de assets
function getAssetUrl($path) {
    if (!isset($_SERVER[\'HTTP_HOST\'])) {
        return $path;
    }
    
    $baseUrl = getBaseUrl();
    $path = ltrim($path, \'/\');
    
    if (strpos($path, \'http\') === 0) {
        return $path;
    }
    
    return $baseUrl . $path;
}

// Função para gerar URL de páginas
function getPageUrl($path) {
    if (!isset($_SERVER[\'HTTP_HOST\'])) {
        return $path;
    }
    
    $baseUrl = getBaseUrl();
    $path = ltrim($path, \'/\');
    
    if (strpos($path, \'http\') === 0) {
        return $path;
    }
    
    // Garantir que sempre seja uma URL absoluta
    return $baseUrl . $path;
}

// Função para redirecionamento com caminho correto
function redirectTo($path) {
    $baseUrl = getBaseUrl();
    $path = ltrim($path, \'/\');
    
    if (strpos($path, \'http\') === 0) {
        header(\'Location: \' . $path);
    } else {
        header(\'Location: \' . $baseUrl . $path);
    }
    exit();
}

// Variável global para conexão
$pdo = null;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
        DB_USER, 
        DB_PASS
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO_ERRMODE);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO_FETCH_MODE);
    
} catch (PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Erro na conexão com o banco de dados. Verifique as configurações.");
}

// Função para obter conexão PDO
function getConnection() {
    global $pdo;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
                DB_USER, 
                DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO_ERRMODE);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO_FETCH_MODE);
        } catch (PDOException $e) {
            error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
            throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>';

    // Fazer backup do arquivo original
    $backup_file = $arquivo_config . '.backup.' . date('Y-m-d_H-i-s');
    if (file_put_contents($backup_file, $conteudo_atual)) {
        echo "<p style='color: green;'>✅ Backup criado: $backup_file</p>";
    }
    
    // Escrever arquivo corrigido
    if (file_put_contents($arquivo_config, $conteudo_corrigido)) {
        echo "<p style='color: green;'>✅ Arquivo $arquivo_config corrigido com sucesso!</p>";
        echo "<p>A função getConnection() foi adicionada.</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro ao escrever arquivo corrigido</p>";
    }
    
} else {
    echo "<p style='color: green;'>✅ Função getConnection() já existe no arquivo</p>";
}

echo "<h3>🧪 Testando função getConnection()...</h3>";

try {
    require_once $arquivo_config;
    
    if (function_exists('getConnection')) {
        echo "<p style='color: green;'>✅ Função getConnection() encontrada</p>";
        
        try {
            $pdo = getConnection();
            echo "<p style='color: green;'>✅ Conexão estabelecida com sucesso</p>";
            
            // Testar uma query simples
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            if ($result && $result['test'] == 1) {
                echo "<p style='color: green;'>✅ Query de teste executada com sucesso</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro na conexão: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Função getConnection() ainda não encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao carregar arquivo: " . $e->getMessage() . "</p>";
}

echo "<h3>📋 Resumo</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Arquivo:</strong> $arquivo_config</p>";
echo "<p><strong>Status:</strong> " . (function_exists('getConnection') ? '✅ Funcionando' : '❌ Com problemas') . "</p>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🎯 Próximos Passos:</h4>";
echo "<ol>";
echo "<li>Se a função estiver funcionando, use o script de migração normal</li>";
echo "<li>Se ainda houver problemas, use o script migrar_independente.php</li>";
echo "<li>Verifique as configurações de banco no arquivo</li>";
echo "</ol>";
echo "</div>";
?>
