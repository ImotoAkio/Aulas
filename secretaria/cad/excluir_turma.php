<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$turma_id = $_GET['id'] ?? null;
if (!$turma_id || !is_numeric($turma_id)) {
    $_SESSION['erro'] = "ID da turma inválido.";
    header('Location: turmas.php');
    exit();
}

try {
    // Verificar se a turma existe
    $stmt = $pdo->prepare("
        SELECT t.*, 
               COUNT(a.id) as total_alunos
        FROM turmas t 
        LEFT JOIN alunos a ON t.id = a.turma_id 
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$turma_id]);
    $turma = $stmt->fetch();
    
    if (!$turma) {
        $_SESSION['erro'] = "Turma não encontrada.";
        header('Location: turmas.php');
        exit();
    }
    
    // Verificar se há alunos na turma
    if ($turma['total_alunos'] > 0) {
        $_SESSION['erro'] = "Não é possível excluir a turma '{$turma['nome']}' pois ela possui {$turma['total_alunos']} aluno(s) cadastrado(s). Remova os alunos primeiro.";
        header('Location: turmas.php');
        exit();
    }
    
    // Confirmar exclusão
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_exclusao'])) {
        $pdo->beginTransaction();
        
        try {
            // Excluir a turma
            $stmt = $pdo->prepare("DELETE FROM turmas WHERE id = ?");
            $stmt->execute([$turma_id]);
            
            $pdo->commit();
            
            $_SESSION['sucesso'] = "Turma '{$turma['nome']}' excluída com sucesso!";
            header('Location: turmas.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
} catch (PDOException $e) {
    error_log("Erro ao excluir turma: " . $e->getMessage());
    $_SESSION['erro'] = "Erro interno do sistema. Tente novamente.";
    header('Location: turmas.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Excluir Turma - Rosa de Sharom</title>
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="shortcut icon" href="../../assets/images/favicon.png" />
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .delete-modal {
      background: white;
      padding: 40px;
      border-radius: 15px;
      text-align: center;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideIn 0.5s ease-out;
      position: relative;
      overflow: hidden;
    }
    
    .delete-modal::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #dc3545, #e74c3c, #c0392b);
    }
    
    .delete-icon {
      font-size: 80px;
      color: #dc3545;
      margin-bottom: 20px;
      animation: shake 0.6s ease-out;
    }
    
    .delete-title {
      font-size: 28px;
      color: #dc3545;
      margin-bottom: 15px;
      font-weight: bold;
    }
    
    .delete-message {
      font-size: 18px;
      color: #666;
      margin-bottom: 30px;
      line-height: 1.6;
    }
    
    .turma-info {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
      border-left: 4px solid #dc3545;
    }
    
    .turma-name {
      font-size: 20px;
      font-weight: bold;
      color: #333;
      margin-bottom: 10px;
    }
    
    .turma-details {
      color: #666;
      font-size: 14px;
    }
    
    .warning-message {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      color: #856404;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      font-size: 14px;
    }
    
    .button-group {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }
    
    .btn {
      padding: 12px 30px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-danger {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
      color: white;
    }
    
    .btn-danger:hover {
      background: linear-gradient(45deg, #c82333, #dc3545);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
    }
    
    .btn-secondary {
      background: linear-gradient(45deg, #6c757d, #868e96);
      color: white;
    }
    
    .btn-secondary:hover {
      background: linear-gradient(45deg, #5a6268, #6c757d);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
    }
    
    @keyframes slideIn {
      from { 
        transform: translateY(-100px) scale(0.9);
        opacity: 0;
      }
      to { 
        transform: translateY(0) scale(1);
        opacity: 1;
      }
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }
  </style>
</head>

<body>
  <div class="delete-modal">
    <i class="mdi mdi-delete-alert delete-icon"></i>
    <h4 class="delete-title">Confirmar Exclusão</h4>
    <p class="delete-message">
      Tem certeza que deseja excluir esta turma? Esta ação não pode ser desfeita.
    </p>
    
    <div class="turma-info">
      <div class="turma-name"><?= htmlspecialchars($turma['nome']) ?></div>
      <div class="turma-details">
        <strong>Ano Letivo:</strong> <?= htmlspecialchars($turma['ano_letivo']) ?><br>
        <strong>ID:</strong> <?= $turma['id'] ?><br>
        <strong>Alunos:</strong> <?= $turma['total_alunos'] ?> aluno(s)
        <?php if (!empty($turma['descricao'])): ?>
          <br><strong>Descrição:</strong> <?= htmlspecialchars($turma['descricao']) ?>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="warning-message">
      <i class="mdi mdi-alert"></i>
      <strong>Atenção:</strong> Esta ação irá remover permanentemente a turma do sistema.
    </div>
    
    <form method="POST" class="button-group">
      <button type="submit" name="confirmar_exclusao" class="btn btn-danger">
        <i class="mdi mdi-delete"></i> Sim, Excluir
      </button>
      <a href="turmas.php" class="btn btn-secondary">
        <i class="mdi mdi-close"></i> Cancelar
      </a>
    </form>
  </div>
</body>

</html>
