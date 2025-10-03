<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('login.php');
    exit();
}

$disciplina_id = $_GET['id'] ?? '';

if (empty($disciplina_id) || !is_numeric($disciplina_id)) {
    $_SESSION['erro_disciplina'] = 'ID da disciplina inválido.';
    header('Location: disciplinas.php');
    exit();
}

try {
    // Verificar se a disciplina existe
    $stmt = $pdo->prepare("SELECT nome FROM disciplinas WHERE id = ?");
    $stmt->execute([$disciplina_id]);
    $disciplina = $stmt->fetch();
    
    if (!$disciplina) {
        $_SESSION['erro_disciplina'] = 'Disciplina não encontrada.';
        header('Location: disciplinas.php');
        exit();
    }
    
    // Verificar se a disciplina está sendo usada em notas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notas WHERE disciplina_id = ?");
    $stmt->execute([$disciplina_id]);
    $notas_count = $stmt->fetchColumn();
    
    if ($notas_count > 0) {
        $_SESSION['erro_disciplina'] = 'Não é possível excluir esta disciplina pois ela possui notas associadas.';
        header('Location: disciplinas.php');
        exit();
    }
    
    // Verificar se a disciplina está sendo usada em professores_disciplinas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professores_disciplinas WHERE disciplina_id = ?");
    $stmt->execute([$disciplina_id]);
    $professores_count = $stmt->fetchColumn();
    
    if ($professores_count > 0) {
        $_SESSION['erro_disciplina'] = 'Não é possível excluir esta disciplina pois ela está associada a professores.';
        header('Location: disciplinas.php');
        exit();
    }
    
    // Excluir a disciplina
    $stmt = $pdo->prepare("DELETE FROM disciplinas WHERE id = ?");
    $stmt->execute([$disciplina_id]);
    
    $_SESSION['sucesso_disciplina'] = 'Disciplina "' . $disciplina['nome'] . '" excluída com sucesso!';
    
} catch (PDOException $e) {
    $_SESSION['erro_disciplina'] = 'Erro ao excluir disciplina: ' . $e->getMessage();
    error_log("Erro ao excluir disciplina: " . $e->getMessage());
}

header('Location: disciplinas.php');
exit();
?>
