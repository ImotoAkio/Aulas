<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cadastrar Secretário</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../../assets/vendors/select2/select2.min.css">
  <link rel="stylesheet" href="../../assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../../assets/images/favicon.png" />
  <style>
    .step-indicator {
      display: flex;
      justify-content: center;
      margin-bottom: 30px;
    }
    .step {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #e9ecef;
      color: #6c757d;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 10px;
      font-weight: bold;
    }
    .step.active {
      background-color: #007bff;
      color: white;
    }
    .step.completed {
      background-color: #28a745;
      color: white;
    }
    .step-content {
      display: none;
    }
    .step-content.active {
      display: block;
    }
    .btn-navigation {
      margin: 10px 5px;
    }
    .alert {
      margin-bottom: 20px;
    }
  </style>
</head>

<body>
  <div class="container-scroller">
    <!-- partial:../partials/_navbar.html -->
    <?php include '../partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../partials/_sidebar.html -->
      <?php include '../partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Cadastrar Secretário </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Secretários</li>
              </ol>
            </nav>
          </div>

          <!-- Indicador de Progresso -->
          <div class="step-indicator">
            <div class="step active" id="step1-indicator">1</div>
            <div class="step" id="step2-indicator">2</div>
          </div>

          <!-- Mensagens de Feedback -->
          <?php if (isset($_SESSION['erro_cadastro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= $_SESSION['erro_cadastro'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['erro_cadastro']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['sucesso_cadastro'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $_SESSION['sucesso_cadastro'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['sucesso_cadastro']); ?>
          <?php endif; ?>

          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <form id="secretarioForm" method="POST" action="salvar_secretario.php" class="forms-sample">
                    
                    <!-- ETAPA 1: Dados Básicos -->
                    <div class="step-content active" id="step1">
                      <h4 class="mb-4">Etapa 1: Dados Básicos</h4>
                      
                      <div class="row">
                        <div class="col-md-12">
                          <div class="form-group">
                            <label>Nome completo *</label>
                            <input type="text" name="nome" class="form-control" required>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Senha *</label>
                            <input type="password" name="senha" class="form-control" required>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Confirmar Senha *</label>
                            <input type="password" name="confirmar_senha" class="form-control" required>
                          </div>
                        </div>
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
                      </div>
                    </div>

                    <!-- ETAPA 2: Confirmação -->
                    <div class="step-content" id="step2">
                      <h4 class="mb-4">Etapa 2: Confirmação</h4>
                      
                      <div class="alert alert-info">
                        <h5><i class="mdi mdi-information-outline"></i> Informações do Secretário</h5>
                        <p>O secretário será cadastrado com acesso de <strong>coordenador</strong>, permitindo:</p>
                        <ul>
                          <li>Cadastrar alunos, professores e outros secretários</li>
                          <li>Gerenciar planos de aula</li>
                          <li>Gerenciar notas</li>
                          <li>Gerenciar pareceres pedagógicos</li>
                          <li>Acessar todas as funcionalidades do sistema</li>
                        </ul>
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Etapa Anterior</button>
                        <button type="submit" class="btn btn-gradient-primary">Cadastrar Secretário</button>
                      </div>
                    </div>

                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:../../partials/_footer.html -->
        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2023 <a
                href="https://www.bootstrapdash.com/" target="_blank">BootstrapDash</a>. All rights reserved.</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i
                class="mdi mdi-heart text-danger"></i></span>
          </div>
        </footer>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Plugin js for this page -->
  <script src="../../assets/vendors/select2/select2.min.js"></script>
  <script src="../../assets/vendors/typeahead.js/typeahead.bundle.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../../assets/js/off-canvas.js"></script>
  <script src="../../assets/js/misc.js"></script>
  <script src="../../assets/js/settings.js"></script>
  <script src="../../assets/js/todolist.js"></script>
  <script src="../../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../../assets/js/file-upload.js"></script>
  <script src="../../assets/js/typeahead.js"></script>
  <script src="../../assets/js/select2.js"></script>
  <!-- End custom js for this page -->

  <script>
    let currentStep = 1;
    const totalSteps = 2;

    function showStep(step) {
      // Esconder todas as etapas
      for (let i = 1; i <= totalSteps; i++) {
        document.getElementById(`step${i}`).classList.remove('active');
        document.getElementById(`step${i}-indicator`).classList.remove('active', 'completed');
      }

      // Mostrar etapa atual
      document.getElementById(`step${step}`).classList.add('active');
      document.getElementById(`step${step}-indicator`).classList.add('active');

      // Marcar etapas anteriores como completadas
      for (let i = 1; i < step; i++) {
        document.getElementById(`step${i}-indicator`).classList.add('completed');
      }
    }

    function nextStep() {
      if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
          currentStep++;
          showStep(currentStep);
        }
      }
    }

    function prevStep() {
      if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
      }
    }

    function validateCurrentStep() {
      const currentStepElement = document.getElementById(`step${currentStep}`);
      const requiredFields = currentStepElement.querySelectorAll('[required]');
      let isValid = true;

      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          field.classList.add('is-invalid');
          isValid = false;
        } else {
          field.classList.remove('is-invalid');
        }
      });

      // Validação específica para a etapa 1 (senhas)
      if (currentStep === 1) {
        const senha = document.querySelector('input[name="senha"]').value;
        const confirmarSenha = document.querySelector('input[name="confirmar_senha"]').value;
        
        if (senha !== confirmarSenha) {
          alert('As senhas não coincidem!');
          return false;
        }
        
        if (senha.length < 6) {
          alert('A senha deve ter pelo menos 6 caracteres!');
          return false;
        }
      }

      if (!isValid) {
        alert('Por favor, preencha todos os campos obrigatórios.');
      }

      return isValid;
    }
  </script>
</body>

</html>
