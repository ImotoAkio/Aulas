<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// Buscar turmas disponíveis
$turmas = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cadastrar Aluno</title>
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
    <!-- partial:../../partials/_navbar.html -->
    <?php include '../partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../../partials/_sidebar.html -->
      <?php include '../partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Cadastrar Aluno </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Alunos</li>
              </ol>
            </nav>
          </div>

          <!-- Indicador de Progresso -->
          <div class="step-indicator">
            <div class="step active" id="step1-indicator">1</div>
            <div class="step" id="step2-indicator">2</div>
            <div class="step" id="step3-indicator">3</div>
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
                  <form id="alunoForm" method="POST" action="salvar_aluno_simples.php" class="forms-sample">
                    
                    <!-- ETAPA 1: Dados Pessoais -->
                    <div class="step-content active" id="step1">
                      <h4 class="mb-4">Etapa 1: Dados Pessoais</h4>
                      
                      <div class="row">
                        <div class="col-md-12">
                          <div class="form-group">
                            <label>Nome completo *</label>
                            <input type="text" name="nome_completo" class="form-control" required>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Data de nascimento *</label>
                            <input type="date" name="data_nascimento" class="form-control" required>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Sexo *</label>
                            <select name="sexo" class="form-control" required>
                              <option value="">Selecione</option>
                              <option value="M">Masculino</option>
                              <option value="F">Feminino</option>
                              <option value="Outro">Outro</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Nacionalidade</label>
                            <input type="text" name="nacionalidade" class="form-control" value="Brasileira">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Naturalidade (Cidade)</label>
                            <input type="text" name="naturalidade_cidade" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Naturalidade (Estado)</label>
                            <input type="text" name="naturalidade_estado" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="cpf" class="form-control" required maxlength="14" placeholder="000.000.000-00">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>RG</label>
                            <input type="text" name="rg" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Turma *</label>
                            <select name="turma_id" class="form-control" required>
                              <option value="">Selecione a turma</option>
                              <?php foreach ($turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
                      </div>
                    </div>

                    <!-- ETAPA 2: Endereço e Contato -->
                    <div class="step-content" id="step2">
                      <h4 class="mb-4">Etapa 2: Endereço e Contato</h4>
                      
                      <div class="row">
                        <div class="col-md-8">
                          <div class="form-group">
                            <label>Endereço</label>
                            <input type="text" name="endereco" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Número</label>
                            <input type="text" name="numero" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Complemento</label>
                            <input type="text" name="complemento" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Bairro</label>
                            <input type="text" name="bairro" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>CEP</label>
                            <input type="text" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" name="cidade" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Estado</label>
                            <input type="text" name="estado" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Telefone 1</label>
                            <input type="text" name="telefone1" class="form-control" placeholder="(00) 00000-0000">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Telefone 2</label>
                            <input type="text" name="telefone2" class="form-control" placeholder="(00) 00000-0000">
                          </div>
                        </div>
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Etapa Anterior</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
                      </div>
                    </div>

                    <!-- ETAPA 3: Saúde e Responsáveis -->
                    <div class="step-content" id="step3">
                      <h4 class="mb-4">Etapa 3: Saúde e Responsáveis</h4>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Tipo sanguíneo</label>
                            <select name="tipo_sanguineo" class="form-control">
                              <option value="">Selecione</option>
                              <option value="A">A</option>
                              <option value="B">B</option>
                              <option value="AB">AB</option>
                              <option value="O">O</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Fator RH</label>
                            <select name="fator_rh" class="form-control">
                              <option value="">Selecione</option>
                              <option value="+">Positivo (+)</option>
                              <option value="-">Negativo (-)</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label>Alergias</label>
                        <textarea name="alergias" class="form-control" rows="3" placeholder="Descreva as alergias conhecidas..."></textarea>
                      </div>

                      <h5 class="mt-4 mb-3">Responsáveis</h5>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Nome da mãe</label>
                            <input type="text" name="nome_mae" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF da mãe</label>
                            <input type="text" name="cpf_mae" class="form-control" maxlength="14">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Nome do pai</label>
                            <input type="text" name="nome_pai" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF do pai</label>
                            <input type="text" name="cpf_pai" class="form-control" maxlength="14">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Nome do responsável legal</label>
                            <input type="text" name="nome_resp_legal" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF do responsável legal</label>
                            <input type="text" name="cpf_resp_legal" class="form-control" maxlength="14">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Profissão do responsável legal</label>
                            <input type="text" name="profissao_resp_legal" class="form-control">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Grau de parentesco</label>
                            <input type="text" name="grau_parentesco_resp_legal" class="form-control">
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label>Local de trabalho do responsável legal</label>
                        <input type="text" name="local_trabalho_resp_legal" class="form-control">
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Etapa Anterior</button>
                        <button type="submit" class="btn btn-gradient-primary">Cadastrar Aluno</button>
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
    const totalSteps = 3;

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

      if (!isValid) {
        alert('Por favor, preencha todos os campos obrigatórios.');
      }

      return isValid;
    }

    // Máscaras para CPF e telefones
    document.addEventListener('DOMContentLoaded', function() {
      // Máscara para CPF
      const cpfFields = document.querySelectorAll('input[name*="cpf"]');
      cpfFields.forEach(field => {
        field.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            e.target.value = value;
          }
        });
      });

      // Máscara para telefones
      const phoneFields = document.querySelectorAll('input[name*="telefone"]');
      phoneFields.forEach(field => {
        field.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          if (value.length <= 11) {
            if (value.length === 11) {
              value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else {
              value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            e.target.value = value;
          }
        });
      });

      // Máscara para CEP
      const cepField = document.querySelector('input[name="cep"]');
      if (cepField) {
        cepField.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          if (value.length <= 8) {
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            e.target.value = value;
          }
        });
      }
    });
  </script>
</body>

</html>