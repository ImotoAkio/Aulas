<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('login.php');
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: secretario.php');
    exit();
}

try {
    // Iniciar transação
    $pdo->beginTransaction();

    // Coletar e validar dados do formulário
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? ''
    ];

    // Validações básicas
    if (empty($dados['nome'])) {
        throw new Exception('Nome é obrigatório.');
    }

    if (empty($dados['email'])) {
        throw new Exception('Email é obrigatório.');
    }

    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido.');
    }

    if (empty($dados['senha'])) {
        throw new Exception('Senha é obrigatória.');
    }

    if ($dados['senha'] !== $dados['confirmar_senha']) {
        throw new Exception('As senhas não coincidem.');
    }

    if (strlen($dados['senha']) < 6) {
        throw new Exception('A senha deve ter pelo menos 6 caracteres.');
    }

    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$dados['email']]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está cadastrado.');
    }

    // Criptografar senha
    $senha_criptografada = password_hash($dados['senha'], PASSWORD_DEFAULT);

    // Inserir na tabela usuarios (apenas campos que existem)
    $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'coordenador')";
    
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute([
        $dados['nome'],
        $dados['email'],
        $senha_criptografada
    ]);

    $secretario_id = $pdo->lastInsertId();

    // Commit da transação
    $pdo->commit();

    // Sucesso
    $_SESSION['sucesso_cadastro'] = "Secretário cadastrado com sucesso!";
    header('Location: sucesso_cadastro_secretario.php');
    exit();

} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro PDO ao cadastrar secretário: " . $e->getMessage() . " - Código: " . $e->getCode());
    error_log("SQL State: " . $e->getCode());
    error_log("Trace: " . $e->getTraceAsString());
    $_SESSION['erro_cadastro'] = "Erro interno do sistema. Tente novamente.";
    header('Location: secretario.php');
    exit();
} catch (Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao cadastrar secretário: " . $e->getMessage());
    $_SESSION['erro_cadastro'] = $e->getMessage();
    header('Location: secretario.php');
    exit();
}
?>
