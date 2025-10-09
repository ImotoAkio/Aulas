<!DOCTYPE html>
<html>
<head>
    <title>Teste de Servidor</title>
</head>
<body>
    <h2>Teste de Servidor</h2>
    
    <h3>Teste 1: HTML Funcionando?</h3>
    <p>Se você está vendo esta mensagem, o HTML está funcionando.</p>
    
    <h3>Teste 2: PHP Funcionando?</h3>
    <p>Data/Hora atual: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <h3>Teste 3: Informações do PHP</h3>
    <?php if (function_exists('phpinfo')): ?>
        <p>✅ PHP está ativo!</p>
        <p>Versão: <?php echo phpversion(); ?></p>
    <?php else: ?>
        <p>❌ PHP não está funcionando</p>
    <?php endif; ?>
    
    <h3>Teste 4: Conexão com Banco</h3>
    <?php
    try {
        $host = 'localhost';
        $dbname = 'u894209272_app';
        $user = 'u894209272_app';
        $pass = 'Akio2604*';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        $pdo = new PDO($dsn, $user, $pass);
        echo "<p style='color: green;'>✅ Conexão com banco funcionando!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html>
