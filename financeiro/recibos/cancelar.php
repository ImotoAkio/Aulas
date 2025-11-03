<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

$pdo = getConnection();
$recibo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$motivo = isset($_GET['motivo']) ? trim($_GET['motivo']) : '';

if ($recibo_id > 0 && $motivo !== '') {
    try {
        $stmt = $pdo->prepare("
            UPDATE recibos 
            SET status='cancelado', 
                cancelado_por=:usuario_id, 
                cancelado_em=NOW(), 
                motivo_cancelamento=:motivo
            WHERE id=:id
        ");
        $stmt->execute([
            ':id' => $recibo_id,
            ':usuario_id' => $_SESSION['usuario_id'],
            ':motivo' => $motivo
        ]);
        
        header('Location: index.php?msg=Recibo cancelado com sucesso!');
        exit;
    } catch (Exception $e) {
        error_log("Erro ao cancelar recibo: " . $e->getMessage());
        header('Location: index.php?erro=Erro ao cancelar recibo');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>

