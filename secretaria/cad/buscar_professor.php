<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do professor não fornecido']);
    exit();
}

$professor_id = $_GET['id'];

try {
    // Buscar dados básicos do professor
    $stmt = $pdo->prepare("
        SELECT id, nome, email
        FROM usuarios 
        WHERE id = ? AND tipo = 'professor'
    ");
    $stmt->execute([$professor_id]);
    $professor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$professor) {
        echo json_encode(['success' => false, 'message' => 'Professor não encontrado']);
        exit();
    }
    
    // Buscar disciplinas do professor
    $disciplinas_professor = [];
    try {
        $stmt = $pdo->prepare("
            SELECT disciplina_id
            FROM professores_disciplinas
            WHERE professor_id = ?
        ");
        $stmt->execute([$professor_id]);
        $disciplinas_professor = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erro ao buscar disciplinas do professor: " . $e->getMessage());
    }
    
    // Buscar turmas do professor
    $turmas_professor = [];
    try {
        $stmt = $pdo->prepare("
            SELECT turma_id
            FROM professores_turmas
            WHERE professor_id = ?
        ");
        $stmt->execute([$professor_id]);
        $turmas_professor = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erro ao buscar turmas do professor: " . $e->getMessage());
    }
    
    // Adicionar disciplinas e turmas ao array do professor
    $professor['disciplinas'] = $disciplinas_professor;
    $professor['turmas'] = $turmas_professor;
    
    echo json_encode([
        'success' => true,
        'professor' => $professor
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar professor: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>
