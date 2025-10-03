<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $disciplina_id = $_POST['disciplina_id'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    
    // Validações
    if (empty($nome)) {
        $_SESSION['erro_disciplina'] = 'Nome da disciplina é obrigatório.';
        header('Location: disciplinas.php');
        exit();
    }
    
    try {
        if (empty($disciplina_id)) {
            // Inserir nova disciplina
            $stmt = $pdo->prepare("INSERT INTO disciplinas (nome) VALUES (?)");
            $stmt->execute([$nome]);
            $_SESSION['sucesso_disciplina'] = 'Disciplina cadastrada com sucesso!';
        } else {
            // Atualizar disciplina existente
            $stmt = $pdo->prepare("UPDATE disciplinas SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $disciplina_id]);
            $_SESSION['sucesso_disciplina'] = 'Disciplina atualizada com sucesso!';
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            $_SESSION['erro_disciplina'] = 'Já existe uma disciplina com este nome.';
        } else {
            $_SESSION['erro_disciplina'] = 'Erro ao salvar disciplina: ' . $e->getMessage();
        }
        error_log("Erro ao salvar disciplina: " . $e->getMessage());
    }
}

header('Location: disciplinas.php');
exit();
?>
