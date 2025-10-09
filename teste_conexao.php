<?php
/**
 * TESTE RÁPIDO DE CONEXÃO
 * Verifica apenas se consegue conectar ao banco
 */

echo "<h2>🧪 TESTE RÁPIDO DE CONEXÃO</h2>";

// Configurações básicas
$host = 'localhost';
$dbname = 'u894209272_app';          // ← NOME DO BANCO CORRETO
$user = 'u894209272_app';            // ← USUÁRIO CORRETO
$pass = 'Akio2604*';                 // ← SENHA CORRETA

echo "<h3>🔧 Configurações:</h3>";
echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Banco:</strong> $dbname</p>";
echo "<p><strong>Usuário:</strong> $user</p>";
echo "<p><strong>Senha:</strong> " . (empty($pass) ? '(vazia)' : '(definida)') . "</p>";

echo "<h3>🔌 Testando conexão...</h3>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>CONEXÃO ESTABELECIDA COM SUCESSO!</strong></p>";
    
    // Testar uma query simples
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result && $result['test'] == 1) {
        echo "<p style='color: green;'>✅ Query de teste executada com sucesso</p>";
    }
    
    // Listar algumas tabelas
    $stmt = $pdo->query("SHOW TABLES LIMIT 5");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p style='color: green;'>✅ Encontradas " . count($tabelas) . " tabelas</p>";
    
    if (!empty($tabelas)) {
        echo "<h4>Primeiras tabelas encontradas:</h4>";
        echo "<ul>";
        foreach ($tabelas as $tabela) {
            echo "<li>📋 $tabela</li>";
        }
        echo "</ul>";
    }
    
    echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h4>🎉 SUCESSO!</h4>";
    echo "<p>A conexão com o banco está funcionando perfeitamente!</p>";
    echo "<p>Agora você pode usar o script de migração completo.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h4>❌ ERRO DE CONEXÃO:</h4>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>DSN:</strong> $dsn</p>";
    
    echo "<h4>🔧 POSSÍVEIS SOLUÇÕES:</h4>";
    echo "<ol>";
    echo "<li><strong>Verificar nome do banco:</strong> Confirme se '$dbname' está correto</li>";
    echo "<li><strong>Verificar usuário:</strong> Confirme se '$user' está correto</li>";
    echo "<li><strong>Verificar senha:</strong> Confirme se a senha está correta</li>";
    echo "<li><strong>Verificar host:</strong> Confirme se '$host' está correto</li>";
    echo "<li><strong>Banco ativo:</strong> Verifique se o banco está rodando</li>";
    echo "</ol>";
    
    echo "<h4>📞 PRÓXIMOS PASSOS:</h4>";
    echo "<ol>";
    echo "<li>Entre em contato com o suporte da hospedagem</li>";
    echo "<li>Confirme as credenciais do banco de dados</li>";
    echo "<li>Verifique se o banco está ativo</li>";
    echo "<li>Teste novamente após correções</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Informações do Servidor:</h4>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Sim' : 'Não') . "</li>";
echo "<li><strong>MySQL PDO:</strong> " . (extension_loaded('pdo_mysql') ? 'Sim' : 'Não') . "</li>";
echo "<li><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";
echo "</div>";
?>
