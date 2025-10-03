<?php
session_start();
include('../secretaria/partials/db.php');

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';

// Criar tabela de senhas personalizadas se não existir
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS senhas_personalizadas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            senha_hash VARCHAR(255) NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_aluno_ativo (aluno_id, ativo)
        )
    ");
} catch (PDOException $e) {
    error_log("Erro ao criar tabela de senhas: " . $e->getMessage());
}

// Buscar senha atual do aluno
$senha_personalizada = null;
try {
    $stmt = $pdo->prepare("
        SELECT senha_hash FROM senhas_personalizadas 
        WHERE aluno_id = ? AND ativo = TRUE
    ");
    $stmt->execute([$aluno_id]);
    $senha_personalizada = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao buscar senha personalizada: " . $e->getMessage());
}

// Processar formulário de alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    try {
        // Verificar senha atual
        $senha_atual_correta = false;
        
        if ($senha_personalizada) {
            // Verificar senha personalizada
            $senha_atual_correta = password_verify($senha_atual, $senha_personalizada);
        } else {
            // Verificar senha padrão
            $senha_atual_correta = ($senha_atual === 'CRS2025');
        }
        
        if (!$senha_atual_correta) {
            throw new Exception("Senha atual incorreta.");
        }
        
        // Validar nova senha
        if (empty($nova_senha)) {
            throw new Exception("Nova senha é obrigatória.");
        }
        
        if (strlen($nova_senha) < 6) {
            throw new Exception("A nova senha deve ter pelo menos 6 caracteres.");
        }
        
        if ($nova_senha !== $confirmar_senha) {
            throw new Exception("As senhas não coincidem.");
        }
        
        // Verificar se a nova senha não é a mesma da atual
        if ($senha_personalizada && password_verify($nova_senha, $senha_personalizada)) {
            throw new Exception("A nova senha não pode ser igual à senha atual.");
        }
        
        if (!$senha_personalizada && $nova_senha === 'CRS2025') {
            throw new Exception("A nova senha não pode ser igual à senha atual.");
        }
        
        // Hash da nova senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualizar ou inserir nova senha
        $pdo->beginTransaction();
        
        // Desativar senha anterior se existir
        if ($senha_personalizada) {
            $stmt = $pdo->prepare("
                UPDATE senhas_personalizadas 
                SET ativo = FALSE 
                WHERE aluno_id = ? AND ativo = TRUE
            ");
            $stmt->execute([$aluno_id]);
        }
        
        // Inserir nova senha
        $stmt = $pdo->prepare("
            INSERT INTO senhas_personalizadas (aluno_id, senha_hash) 
            VALUES (?, ?)
        ");
        $stmt->execute([$aluno_id, $nova_senha_hash]);
        
        $pdo->commit();
        
        $mensagem = "Senha alterada com sucesso!";
        $tipo_mensagem = "success";
        
        // Atualizar flag de senha personalizada
        $senha_personalizada = $nova_senha_hash;
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $mensagem = $e->getMessage();
        $tipo_mensagem = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Alterar Senha - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.php -->
    <?php include('partials/_navbar.php'); ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.php -->
      <?php include('partials/_sidebar.php'); ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-lock-reset"></i> Alterar Senha
                  </h4>
                  <p class="card-description">
                    Altere sua senha de acesso ao sistema
                  </p>
                  
                  <?php if ($mensagem): ?>
                    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                      <i class="mdi mdi-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
                      <?= htmlspecialchars($mensagem) ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  <?php endif; ?>
                  
                  <form method="POST" class="forms-sample">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Senha Atual *</label>
                          <input type="password" name="senha_atual" class="form-control" 
                                 placeholder="Digite sua senha atual" required>
                          <small class="form-text text-muted">
                            <i class="mdi mdi-information"></i> 
                            <?php if ($senha_personalizada): ?>
                              Digite sua senha personalizada
                            <?php else: ?>
                              Senha padrão: CRS2025
                            <?php endif; ?>
                          </small>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Nova Senha *</label>
                          <input type="password" name="nova_senha" class="form-control" 
                                 placeholder="Digite a nova senha" required>
                          <small class="form-text text-muted">
                            <i class="mdi mdi-shield-check"></i> 
                            Mínimo 6 caracteres
                          </small>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Confirmar Nova Senha *</label>
                          <input type="password" name="confirmar_senha" class="form-control" 
                                 placeholder="Confirme a nova senha" required>
                          <small class="form-text text-muted">
                            <i class="mdi mdi-shield-check"></i> 
                            Digite a mesma senha novamente
                          </small>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <div class="alert alert-info">
                          <h6><i class="mdi mdi-information"></i> Dicas de Segurança:</h6>
                          <ul class="mb-0">
                            <li>Use uma senha com pelo menos 6 caracteres</li>
                            <li>Combine letras, números e símbolos</li>
                            <li>Evite usar informações pessoais (nome, data de nascimento)</li>
                            <li>Não compartilhe sua senha com outras pessoas</li>
                            <li>Altere sua senha regularmente</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <button type="submit" class="btn btn-gradient-primary mr-2">
                          <i class="mdi mdi-content-save"></i> Alterar Senha
                        </button>
                        <a href="index.php" class="btn btn-light">
                          <i class="mdi mdi-arrow-left"></i> Voltar
                        </a>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.php -->
        <?php include('../secretaria/partials/_footer.php'); ?>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- plugins:js -->
  <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <!-- endinject -->
  
  <script>
    // Validação em tempo real
    $(document).ready(function() {
      $('input[name="nova_senha"]').on('input', function() {
        var senha = $(this).val();
        var $confirmar = $('input[name="confirmar_senha"]');
        
        if (senha.length < 6) {
          $(this).addClass('is-invalid');
          $(this).removeClass('is-valid');
        } else {
          $(this).removeClass('is-invalid');
          $(this).addClass('is-valid');
        }
        
        // Verificar se as senhas coincidem
        if ($confirmar.val() && senha !== $confirmar.val()) {
          $confirmar.addClass('is-invalid');
          $confirmar.removeClass('is-valid');
        } else if ($confirmar.val() && senha === $confirmar.val()) {
          $confirmar.removeClass('is-invalid');
          $confirmar.addClass('is-valid');
        }
      });
      
      $('input[name="confirmar_senha"]').on('input', function() {
        var confirmar = $(this).val();
        var senha = $('input[name="nova_senha"]').val();
        
        if (confirmar && senha !== confirmar) {
          $(this).addClass('is-invalid');
          $(this).removeClass('is-valid');
        } else if (confirmar && senha === confirmar) {
          $(this).removeClass('is-invalid');
          $(this).addClass('is-valid');
        }
      });
    });
  </script>
</body>

</html>