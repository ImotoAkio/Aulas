<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getPageUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$aluno_id = $_GET['id'] ?? null;
if (!$aluno_id) {
    header('Location: listar_alunos.php');
    exit();
}

// Buscar dados do aluno
$aluno = null;
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome 
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch();
    
    if (!$aluno) {
        $_SESSION['erro'] = "Aluno não encontrado.";
        header('Location: listar_alunos.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aluno: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao buscar dados do aluno.";
    header('Location: listar_alunos.php');
    exit();
}

// Buscar turmas disponíveis
$turmas = [];
try {
    $stmt = $pdo->query("SELECT id, nome, ano_letivo FROM turmas ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Coletar dados do formulário
        $dados = [
            'nome_completo' => trim($_POST['nome_completo'] ?? ''),
            'data_nascimento' => $_POST['data_nascimento'] ?? null,
            'sexo' => $_POST['sexo'] ?? null,
            'cpf' => trim($_POST['cpf'] ?? ''),
            'rg' => trim($_POST['rg'] ?? ''),
            'nis' => trim($_POST['nis'] ?? ''),
            'nacionalidade' => trim($_POST['nacionalidade'] ?? ''),
            'naturalidade_cidade' => trim($_POST['naturalidade_cidade'] ?? ''),
            'naturalidade_estado' => trim($_POST['naturalidade_estado'] ?? ''),
            'turma_id' => $_POST['turma_id'] ?? null,
            'endereco' => trim($_POST['endereco'] ?? ''),
            'numero' => trim($_POST['numero'] ?? ''),
            'complemento' => trim($_POST['complemento'] ?? ''),
            'bairro' => trim($_POST['bairro'] ?? ''),
            'cep' => trim($_POST['cep'] ?? ''),
            'cidade' => trim($_POST['cidade'] ?? ''),
            'estado' => trim($_POST['estado'] ?? ''),
            'telefone' => trim($_POST['telefone'] ?? ''),
            'alergias' => trim($_POST['alergias'] ?? ''),
            'nome_mae' => trim($_POST['nome_mae'] ?? ''),
            'cpf_mae' => trim($_POST['cpf_mae'] ?? ''),
            'nome_pai' => trim($_POST['nome_pai'] ?? ''),
            'cpf_pai' => trim($_POST['cpf_pai'] ?? ''),
            'telefone_responsavel' => trim($_POST['telefone_responsavel'] ?? ''),
            'email_responsavel' => trim($_POST['email_responsavel'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? '')
        ];
        
        // Validações básicas
        if (empty($dados['nome_completo'])) {
            throw new Exception('Nome completo é obrigatório.');
        }
        
        if (empty($dados['cpf'])) {
            throw new Exception('CPF é obrigatório.');
        }
        
        // Limpar CPF (remover pontos e traços)
        $cpf_limpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
        if (strlen($cpf_limpo) !== 11) {
            throw new Exception('CPF deve ter 11 dígitos.');
        }
        $dados['cpf'] = $cpf_limpo;
        
        // Verificar se CPF já existe em outro aluno
        $stmt = $pdo->prepare("SELECT id FROM alunos WHERE cpf = ? AND id != ?");
        $stmt->execute([$dados['cpf'], $aluno_id]);
        if ($stmt->fetch()) {
            throw new Exception('Este CPF já está cadastrado para outro aluno.');
        }
        
        // Construir query de UPDATE
        $campos = [];
        $valores = [];
        foreach ($dados as $campo => $valor) {
            if ($valor !== null && $valor !== '') {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            } else {
                $campos[] = "$campo = NULL";
            }
        }
        $valores[] = $aluno_id;
        
        $sql = "UPDATE alunos SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);
        
        $pdo->commit();
        
        $_SESSION['sucesso'] = "Aluno atualizado com sucesso!";
        header('Location: listar_alunos.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro PDO ao atualizar aluno: " . $e->getMessage());
        $erro = "Erro interno do sistema. Tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Editar Aluno - <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2/select2.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css"); ?>">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>" />
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
      cursor: pointer;
      transition: all 0.3s ease;
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
    .progress-bar {
      height: 4px;
      background-color: #e9ecef;
      border-radius: 2px;
      margin: 20px 0;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background-color: #007bff;
      transition: width 0.3s ease;
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
            <h3 class="page-title"> Editar Aluno </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href='<?php echo getPageUrl("secretaria/index.php"); ?>'>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listar_alunos.php">Listar Alunos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar Aluno</li>
              </ol>
            </nav>
          </div>

          <!-- Mensagens de Feedback -->
          <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($erro) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Indicador de Progresso -->
          <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 33%"></div>
          </div>
          
          <div class="step-indicator">
            <div class="step active" id="step1-indicator">1</div>
            <div class="step" id="step2-indicator">2</div>
            <div class="step" id="step3-indicator">3</div>
          </div>

          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Editar Dados do Aluno</h4>
                  <p class="card-description">Complete as informações faltantes do aluno.</p>
                  
                  <form id="alunoForm" method="POST" class="forms-sample">
                    
                    <!-- ETAPA 1: Dados Pessoais -->
                    <div class="step-content active" id="step1">
                      <h4 class="mb-4">Etapa 1: Dados Pessoais</h4>
                      
                      <div class="row">
                        <div class="col-md-12">
                          <div class="form-group">
                            <label>Nome completo *</label>
                            <input type="text" name="nome_completo" class="form-control" required 
                                   value="<?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?>">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Data de nascimento *</label>
                            <input type="date" name="data_nascimento" class="form-control" required
                                   value="<?= $aluno['data_nascimento'] ?? '' ?>">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Sexo *</label>
                            <select name="sexo" class="form-control" required>
                              <option value="">Selecione</option>
                              <option value="M" <?= ($aluno['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                              <option value="F" <?= ($aluno['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                              <option value="Outro" <?= ($aluno['sexo'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Nacionalidade</label>
                            <input type="text" name="nacionalidade" class="form-control" 
                                   value="<?= htmlspecialchars($aluno['nacionalidade'] ?? 'Brasileira') ?>">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Naturalidade (Cidade)</label>
                            <input type="text" name="naturalidade_cidade" class="form-control"
                                   value="<?= htmlspecialchars($aluno['naturalidade_cidade'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Naturalidade (Estado)</label>
                            <input type="text" name="naturalidade_estado" class="form-control"
                                   value="<?= htmlspecialchars($aluno['naturalidade_estado'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="cpf" class="form-control" required maxlength="14" 
                                   placeholder="000.000.000-00" value="<?= htmlspecialchars($aluno['cpf'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>RG</label>
                            <input type="text" name="rg" class="form-control"
                                   value="<?= htmlspecialchars($aluno['rg'] ?? '') ?>">
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
                                <option value="<?= $turma['id'] ?>" <?= ($aluno['turma_id'] == $turma['id']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($turma['nome']) ?> (<?= $turma['ano_letivo'] ?>)
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control"
                                   value="<?= htmlspecialchars($aluno['nis'] ?? '') ?>">
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
                      
                      <div class="form-group">
                        <label>Endereço</label>
                        <input type="text" name="endereco" class="form-control"
                               value="<?= htmlspecialchars($aluno['endereco'] ?? '') ?>">
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Número</label>
                            <input type="text" name="numero" class="form-control"
                                   value="<?= htmlspecialchars($aluno['numero'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Complemento</label>
                            <input type="text" name="complemento" class="form-control"
                                   value="<?= htmlspecialchars($aluno['complemento'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Bairro</label>
                            <input type="text" name="bairro" class="form-control"
                                   value="<?= htmlspecialchars($aluno['bairro'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>CEP</label>
                            <input type="text" name="cep" class="form-control" maxlength="9" 
                                   placeholder="00000-000" value="<?= htmlspecialchars($aluno['cep'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" name="cidade" class="form-control"
                                   value="<?= htmlspecialchars($aluno['cidade'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Estado</label>
                            <input type="text" name="estado" class="form-control"
                                   value="<?= htmlspecialchars($aluno['estado'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="telefone" class="form-control" 
                               placeholder="(00) 00000-0000" value="<?= htmlspecialchars($aluno['telefone'] ?? '') ?>">
                      </div>

                      <div class="form-group">
                        <label>Alergias</label>
                        <textarea name="alergias" class="form-control" rows="3" 
                                  placeholder="Descreva as alergias conhecidas..."><?= htmlspecialchars($aluno['alergias'] ?? '') ?></textarea>
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Etapa Anterior</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
                      </div>
                    </div>

                    <!-- ETAPA 3: Responsáveis -->
                    <div class="step-content" id="step3">
                      <h4 class="mb-4">Etapa 3: Responsáveis</h4>
                      
                      <h5 class="mt-4 mb-3">Responsáveis</h5>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Nome da mãe</label>
                            <input type="text" name="nome_mae" class="form-control"
                                   value="<?= htmlspecialchars($aluno['nome_mae'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF da mãe</label>
                            <input type="text" name="cpf_mae" class="form-control" maxlength="14"
                                   value="<?= htmlspecialchars($aluno['cpf_mae'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Nome do pai</label>
                            <input type="text" name="nome_pai" class="form-control"
                                   value="<?= htmlspecialchars($aluno['nome_pai'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>CPF do pai</label>
                            <input type="text" name="cpf_pai" class="form-control" maxlength="14"
                                   value="<?= htmlspecialchars($aluno['cpf_pai'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Telefone do responsável</label>
                            <input type="text" name="telefone_responsavel" class="form-control" 
                                   placeholder="(00) 00000-0000" value="<?= htmlspecialchars($aluno['telefone_responsavel'] ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Email do responsável</label>
                            <input type="email" name="email_responsavel" class="form-control"
                                   value="<?= htmlspecialchars($aluno['email_responsavel'] ?? '') ?>">
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label>Observações</label>
                        <textarea name="observacoes" class="form-control" rows="3" 
                                  placeholder="Observações adicionais..."><?= htmlspecialchars($aluno['observacoes'] ?? '') ?></textarea>
                      </div>

                      <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Etapa Anterior</button>
                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
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
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <!-- endinject -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Plugin js for this page -->
  <script src="<?php echo getAssetUrl("assets/vendors/select2/select2.min.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/vendors/typeahead.js/typeahead.bundle.min.js"); ?>"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="<?php echo getAssetUrl("assets/js/file-upload.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/typeahead.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/select2.js"); ?>"></script>
  <!-- End custom js for this page -->

  <script>
    let currentStep = 1;
    const totalSteps = 3;

    function updateProgress() {
      const progress = (currentStep / totalSteps) * 100;
      document.getElementById('progressFill').style.width = progress + '%';
    }

    function showStep(step) {
      // Esconder todas as etapas
      for (let i = 1; i <= totalSteps; i++) {
        document.getElementById('step' + i).classList.remove('active');
        document.getElementById('step' + i + '-indicator').classList.remove('active', 'completed');
      }
      
      // Mostrar etapa atual
      document.getElementById('step' + step).classList.add('active');
      document.getElementById('step' + step + '-indicator').classList.add('active');
      
      // Marcar etapas anteriores como completadas
      for (let i = 1; i < step; i++) {
        document.getElementById('step' + i + '-indicator').classList.add('completed');
      }
      
      updateProgress();
    }

    function nextStep() {
      if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
      }
    }

    function prevStep() {
      if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
      }
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
      showStep(1);
      
      // Inicializar Select2
      $('select').select2({
        theme: 'bootstrap'
      });
    });

    // Máscaras para CPF e telefone
    document.addEventListener('DOMContentLoaded', function() {
      // Máscara para CPF
      const cpfFields = document.querySelectorAll('input[name="cpf"], input[name="cpf_mae"], input[name="cpf_pai"]');
      cpfFields.forEach(function(field) {
        field.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            e.target.value = value;
          }
        });
      });

      // Máscara para telefone
      const telefoneFields = document.querySelectorAll('input[name="telefone"], input[name="telefone_responsavel"]');
      telefoneFields.forEach(function(field) {
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
