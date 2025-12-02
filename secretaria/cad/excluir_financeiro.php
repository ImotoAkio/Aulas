<?php
// Garantir utilitários disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../../config/database.php';
}

session_start();
// Somente coordenador pode excluir
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'coordenador') {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('login.php');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        // Garantir que só exclua usuários do tipo financeiro
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id AND tipo = 'financeiro'");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Erro ao excluir usuário financeiro: ' . $e->getMessage());
    }
}

header('Location: financeiro.php');
exit;
