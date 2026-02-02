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
    header('Location: professor.php');
    exit();
}

// Verificar se o ID do professor foi fornecido
if (!isset($_POST['professor_id']) || empty($_POST['professor_id'])) {
    $_SESSION['erro'] = 'ID do professor não fornecido';
    header('Location: professor.php');
    exit();
}

$professor_id = $_POST['professor_id'];
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$disciplinas = $_POST['disciplinas'] ?? [];
$turmas = $_POST['turmas'] ?? [];

// Validações básicas
if (empty($nome) || empty($email)) {
    $_SESSION['erro'] = 'Nome e email são obrigatórios';
    header('Location: professor.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erro'] = 'Email inválido';
    header('Location: professor.php');
    exit();
}

if (empty($disciplinas) || empty($turmas)) {
    $_SESSION['erro'] = 'Disciplinas e turmas são obrigatórias';
    header('Location: professor.php');
    exit();
}

try {
    $pdo->beginTransaction();

    // Verificar se o professor existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'professor'");
    $stmt->execute([$professor_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Professor não encontrado');
    }

    // Verificar se o email já existe em outro professor
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? AND tipo = 'professor'");
    $stmt->execute([$email, $professor_id]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está sendo usado por outro professor');
    }

    // Atualizar dados básicos do professor
    if (!empty($senha)) {
        // Atualizar com nova senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $senha_hash, $professor_id]);
    } else {
        // Atualizar sem alterar senha
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $professor_id]);
    }

    // Remover associações antigas com disciplinas
    try {
        $stmt = $pdo->prepare("DELETE FROM professores_disciplinas WHERE professor_id = ?");
        $stmt->execute([$professor_id]);
    } catch (PDOException $e) {
        error_log("Erro ao remover disciplinas antigas: " . $e->getMessage());
    }

    // Adicionar novas associações com disciplinas
    foreach ($disciplinas as $disciplina_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO professores_disciplinas (professor_id, disciplina_id) VALUES (?, ?)");
            $stmt->execute([$professor_id, $disciplina_id]);
        } catch (PDOException $e) {
            error_log("Erro ao associar disciplina: " . $e->getMessage());
        }
    }

    // Remover associações antigas com turmas
    try {
        $stmt = $pdo->prepare("DELETE FROM professores_turmas WHERE professor_id = ?");
        $stmt->execute([$professor_id]);
    } catch (PDOException $e) {
        error_log("Erro ao remover turmas antigas: " . $e->getMessage());
    }

    // Adicionar novas associações com turmas
    foreach ($turmas as $turma_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO professores_turmas (professor_id, turma_id) VALUES (?, ?)");
            $stmt->execute([$professor_id, $turma_id]);
        } catch (PDOException $e) {
            error_log("Erro ao associar turma: " . $e->getMessage());
        }
    }

    $pdo->commit();

    $_SESSION['sucesso'] = 'Professor atualizado com sucesso!';
    header('Location: professor.php');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao editar professor: " . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao editar professor: ' . $e->getMessage();
    header('Location: professor.php');
    exit();
}
?>