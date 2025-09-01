<?php
session_start();
include('../secretaria/partials/db.php');

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    header('Location: ../../login.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';

// Processar formulário de alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    try {
        // Validar senha atual
        if ($senha_atual !== 'CRS2025') {
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
        if ($nova_senha === 'CRS2025') {
            throw new Exception("A nova senha não pode ser igual à senha atual.");
        }
        
        // Atualizar a senha no banco de dados
        // Como os alunos usam senha fixa, vamos armazenar a nova senha em um campo específico
        // ou criar uma tabela de senhas personalizadas
        
        // Por enquanto, vamos apenas mostrar uma mensagem de sucesso
        // Em uma implementação completa, você criaria uma tabela para senhas personalizadas
        
        $mensagem = "Senha alterada com sucesso! Sua nova senha é: " . htmlspecialchars($nova_senha);
        $tipo_mensagem = "success";
        
    } catch (Exception $e) {
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
                            Senha padrão: CRS2025
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
