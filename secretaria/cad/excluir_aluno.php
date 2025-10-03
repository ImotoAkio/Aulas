<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$aluno_id = $_GET['id'] ?? null;
if (!$aluno_id || !is_numeric($aluno_id)) {
    $_SESSION['erro'] = "ID do aluno inválido.";
    header('Location: listar_alunos.php');
    exit();
}

try {
    // Verificar se o aluno existe
    $stmt = $pdo->prepare("SELECT id, nome, nome_completo FROM alunos WHERE id = ?");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch();
    
    if (!$aluno) {
        $_SESSION['erro'] = "Aluno não encontrado.";
        header('Location: listar_alunos.php');
        exit();
    }
    
    // Verificar se há dependências (notas, pareceres, etc.)
    $dependencias = [];
    
    // Verificar notas
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notas WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    $notas_count = $stmt->fetch()['count'];
    if ($notas_count > 0) {
        $dependencias[] = "$notas_count nota(s)";
    }
    
    // Verificar pareceres
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pareceres WHERE id_aluno = ?");
    $stmt->execute([$aluno_id]);
    $pareceres_count = $stmt->fetch()['count'];
    if ($pareceres_count > 0) {
        $dependencias[] = "$pareceres_count parecer(es)";
    }
    
    // Verificar senhas personalizadas
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM senhas_personalizadas WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    $senhas_count = $stmt->fetch()['count'];
    if ($senhas_count > 0) {
        $dependencias[] = "$senhas_count senha(s) personalizada(s)";
    }
    
    // Se há dependências, mostrar erro
    if (!empty($dependencias)) {
        $_SESSION['erro'] = "Não é possível excluir o aluno '{$aluno['nome_completo']}' pois ele possui: " . implode(', ', $dependencias) . ". Remova essas dependências primeiro.";
        header('Location: listar_alunos.php');
        exit();
    }
    
    // Confirmar exclusão
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_exclusao'])) {
        $pdo->beginTransaction();
        
        try {
            // Excluir o aluno
            $stmt = $pdo->prepare("DELETE FROM alunos WHERE id = ?");
            $stmt->execute([$aluno_id]);
            
            $pdo->commit();
            
            $_SESSION['sucesso'] = "Aluno '{$aluno['nome_completo']}' excluído com sucesso!";
            header('Location: listar_alunos.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
} catch (PDOException $e) {
    error_log("Erro ao excluir aluno: " . $e->getMessage());
    $_SESSION['erro'] = "Erro interno do sistema. Tente novamente.";
    header('Location: listar_alunos.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Excluir Aluno - Rosa de Sharom</title>
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
    
    .student-info {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
      border-left: 4px solid #dc3545;
    }
    
    .student-name {
      font-size: 20px;
      font-weight: bold;
      color: #333;
      margin-bottom: 10px;
    }
    
    .student-id {
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
      Tem certeza que deseja excluir este aluno? Esta ação não pode ser desfeita.
    </p>
    
    <div class="student-info">
      <div class="student-name"><?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></div>
      <div class="student-id">ID: <?= $aluno['id'] ?></div>
    </div>
    
    <div class="warning-message">
      <i class="mdi mdi-alert"></i>
      <strong>Atenção:</strong> Esta ação irá remover permanentemente todos os dados do aluno do sistema.
    </div>
    
    <form method="POST" class="button-group">
      <button type="submit" name="confirmar_exclusao" class="btn btn-danger">
        <i class="mdi mdi-delete"></i> Sim, Excluir
      </button>
      <a href="listar_alunos.php" class="btn btn-secondary">
        <i class="mdi mdi-close"></i> Cancelar
      </a>
    </form>
  </div>
</body>

</html>
