<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
  require_once __DIR__ . '/../../config/database.php';
  redirectTo('login.php');
  exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Verificar mensagens de sessão
if (isset($_SESSION['sucesso'])) {
  $mensagem = $_SESSION['sucesso'];
  $tipo_mensagem = 'success';
  unset($_SESSION['sucesso']);
} elseif (isset($_SESSION['erro'])) {
  $mensagem = $_SESSION['erro'];
  $tipo_mensagem = 'danger';
  unset($_SESSION['erro']);
}

// Processar exclusão de professor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_professor'])) {
  $professor_id = $_POST['professor_id'];

  try {
    $pdo->beginTransaction();

    // Remover dados relacionados (planos, notas, pareceres, associações)
    $tables = ['planos_aula', 'notas', 'pareceres', 'professores_disciplinas', 'professores_turmas'];
    foreach ($tables as $table) {
      try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE professor_id = ?");
        $stmt->execute([$professor_id]);
      } catch (PDOException $e) {
        error_log("Erro ao limpar tabela $table: " . $e->getMessage());
      }
    }

    // Remover o professor
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'professor'");
    $stmt->execute([$professor_id]);

    if ($stmt->rowCount() > 0) {
      $pdo->commit();
      $mensagem = "Professor excluído com sucesso!";
      $tipo_mensagem = "success";
    } else {
      $pdo->rollBack();
      $mensagem = "Professor não encontrado.";
      $tipo_mensagem = "warning";
    }
  } catch (Exception $e) {
    $pdo->rollBack();
    $mensagem = "Erro ao excluir: " . $e->getMessage();
    $tipo_mensagem = "danger";
  }
}

// Buscar dados para os selects
$disciplinas = [];
$turmas = [];
$professores = [];

try {
  $disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll();
  $turmas = $pdo->query("SELECT id, nome, ano_letivo FROM turmas ORDER BY nome")->fetchAll();

  // Buscar professores e seus dados
  $stmt = $pdo->query("SELECT id, nome, email FROM usuarios WHERE tipo = 'professor' ORDER BY nome");
  $professores_basicos = $stmt->fetchAll();

  foreach ($professores_basicos as $professor) {
    // Buscar disciplinas
    $stmt = $pdo->prepare("
            SELECT d.nome FROM disciplinas d
            INNER JOIN professores_disciplinas pd ON d.id = pd.disciplina_id
            WHERE pd.professor_id = ? ORDER BY d.nome
        ");
    $stmt->execute([$professor['id']]);
    $disciplinas_prof = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Buscar turmas
    $stmt = $pdo->prepare("
            SELECT t.nome FROM turmas t
            INNER JOIN professores_turmas pt ON t.id = pt.turma_id
            WHERE pt.professor_id = ? ORDER BY t.nome
        ");
    $stmt->execute([$professor['id']]);
    $turmas_prof = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $professores[] = [
      'id' => $professor['id'],
      'nome' => $professor['nome'],
      'email' => $professor['email'],
      'disciplinas' => $disciplinas_prof,
      'turmas' => implode(', ', $turmas_prof)
    ];
  }
} catch (PDOException $e) {
  error_log("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Gerenciar Professores</title>

  <!-- Plugins CSS -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2/select2.min.css"); ?>">
  <link rel="stylesheet"
    href="<?php echo getAssetUrl("assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css"); ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>" />

  <style>
    /* Modern Modal Styles */
    .modal-content {
      border: none;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .modal-header-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 25px 30px;
      border: none;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-header-gradient .modal-title {
      color: white !important;
      font-weight: 600;
      font-size: 1.5rem;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .modal-header-gradient p {
      color: rgba(255, 255, 255, 0.9);
      margin-bottom: 0;
      font-size: 0.9rem;
    }

    .btn-close-modal {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: all 0.3s ease;
      cursor: pointer;
      outline: none !important;
    }

    .btn-close-modal:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: rotate(90deg) scale(1.1);
    }

    .btn-close-modal i {
      font-size: 20px;
    }

    /* Step Indicator */
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
      transition: all 0.3s ease;
    }

    .step.active {
      background-color: #007bff;
      color: white;
      box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.2);
    }

    .step.completed {
      background-color: #28a745;
      color: white;
    }

    .step-content {
      display: none;
      animation: fadeIn 0.4s ease;
    }

    .step-content.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Form Controls */
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .select2-container--bootstrap .select2-selection {
      border: 2px solid #e9ecef;
      border-radius: 8px;
      min-height: 45px;
      padding: 8px 12px;
    }

    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 15px;
      color: white;
      padding: 4px 12px;
    }

    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
    }

    .card:hover {
      transform: translateY(-2px);
    }
  </style>
</head>

<body>
  <div class="container-scroller">
    <?php include '../partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include '../partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">

          <!-- Page Header -->
          <div class="page-header">
            <h3 class="page-title"> Gerenciar Professores </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Professores</li>
              </ol>
            </nav>
          </div>

          <!-- Feedback Messages -->
          <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
              <i class="mdi mdi-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
              <?= htmlspecialchars($mensagem) ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
          <?php endif; ?>

          <!-- Main Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">
                      <i class="mdi mdi-account-multiple"></i> Professores Cadastrados
                    </h4>
                    <button type="button" class="btn btn-gradient-primary" data-toggle="modal"
                      data-target="#modalCadastro">
                      <i class="mdi mdi-plus"></i> Novo Professor
                    </button>
                  </div>

                  <?php if (empty($professores)): ?>
                    <div class="text-center py-5">
                      <i class="mdi mdi-account-multiple-outline" style="font-size: 64px; color: #ccc;"></i>
                      <h5 class="mt-3 text-muted">Nenhum professor cadastrado</h5>
                    </div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-hover" id="tabelaProfessores">
                        <thead>
                          <tr>
                            <th>Nome</th>
                            <th>Disciplinas</th>
                            <th>Ações</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($professores as $professor): ?>
                            <tr>
                              <td>
                                <div class="d-flex align-items-center">
                                  <div class="mr-3">
                                    <i class="mdi mdi-account-circle text-primary" style="font-size: 24px;"></i>
                                  </div>
                                  <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($professor['nome']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($professor['email']) ?></small>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <?php if (count($professor['disciplinas']) > 6): ?>
                                  <span class="badge badge-info">Polivalente</span>
                                <?php elseif (!empty($professor['disciplinas'])): ?>
                                  <span
                                    class="badge badge-info"><?= htmlspecialchars(implode(', ', $professor['disciplinas'])) ?></span>
                                <?php else: ?>
                                  <span class="text-muted">Nenhuma</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <div class="btn-group">
                                  <button class="btn btn-outline-primary btn-sm btn-editar"
                                    data-id="<?= $professor['id'] ?>">
                                    <i class="mdi mdi-pencil"></i>
                                  </button>
                                  <button class="btn btn-outline-danger btn-sm btn-excluir"
                                    data-id="<?= $professor['id'] ?>"
                                    data-nome="<?= htmlspecialchars($professor['nome']) ?>">
                                    <i class="mdi mdi-delete"></i>
                                  </button>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Cadastro -->
          <div class="modal fade" id="modalCadastro" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
              <div class="modal-content">
                <div class="modal-header modal-header-gradient">
                  <div class="d-flex align-items-center">
                    <div class="mr-3">
                      <div
                        style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-account-plus" style="font-size: 24px; color: white;"></i>
                      </div>
                    </div>
                    <div>
                      <h4 class="modal-title mb-0">Cadastrar Novo Professor</h4>
                      <p>Preencha os dados para adicionar um novo professor</p>
                    </div>
                  </div>
                  <button type="button" class="btn-close-modal" data-dismiss="modal">
                    <i class="mdi mdi-close"></i>
                  </button>
                </div>
                <div class="modal-body p-4">
                  <div class="step-indicator">
                    <div class="step active" id="step1-indicator">1</div>
                    <div class="step" id="step2-indicator">2</div>
                  </div>

                  <form id="professorForm" method="POST" action="salvar_professor.php">
                    <!-- Step 1 -->
                    <div class="step-content active" id="step1">
                      <div class="card mb-4">
                        <div class="card-body">
                          <h6 class="mb-4 text-primary"><i class="mdi mdi-account-card-details mr-2"></i>Dados Pessoais
                          </h6>
                          <div class="form-group">
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required>
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
                          <div class="form-group">
                            <label>Confirmar Senha *</label>
                            <input type="password" name="confirmar_senha" class="form-control" required>
                          </div>
                        </div>
                      </div>
                      <div class="text-right">
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                          Próxima Etapa <i class="mdi mdi-arrow-right ml-1"></i>
                        </button>
                      </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="step-content" id="step2">
                      <div class="card mb-4">
                        <div class="card-body">
                          <h6 class="mb-4 text-primary"><i class="mdi mdi-book-open-variant mr-2"></i>Atribuições</h6>

                          <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                              <label>Disciplinas *</label>
                              <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="checkPolivalenteCadastro">
                                <label class="custom-control-label" for="checkPolivalenteCadastro">Polivalente
                                  (Todas)</label>
                              </div>
                            </div>
                            <select name="disciplinas[]" id="selectDisciplinasCadastro" class="form-control select2"
                              multiple required style="width: 100%">
                              <?php foreach ($disciplinas as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="form-group">
                            <label>Turmas *</label>
                            <select name="turmas[]" id="selectTurmasCadastro" class="form-control select2" multiple
                              required style="width: 100%">
                              <?php foreach ($turmas as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?> -
                                  <?= $t['ano_letivo'] ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="text-right">
                        <button type="button" class="btn btn-secondary mr-2" onclick="prevStep()">
                          <i class="mdi mdi-arrow-left mr-1"></i> Voltar
                        </button>
                        <button type="submit" class="btn btn-gradient-primary">
                          <i class="mdi mdi-check mr-1"></i> Finalizar Cadastro
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Editar -->
          <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
              <div class="modal-content">
                <div class="modal-header modal-header-gradient">
                  <div class="d-flex align-items-center">
                    <div class="mr-3">
                      <div
                        style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-account-edit" style="font-size: 24px; color: white;"></i>
                      </div>
                    </div>
                    <div>
                      <h4 class="modal-title mb-0">Editar Professor</h4>
                      <p>Atualize as informações do professor</p>
                    </div>
                  </div>
                  <button type="button" class="btn-close-modal" data-dismiss="modal">
                    <i class="mdi mdi-close"></i>
                  </button>
                </div>
                <div class="modal-body p-4">
                  <form id="formEditar" method="POST" action="editar_professor.php">
                    <input type="hidden" name="professor_id" id="editar_professor_id">

                    <div class="card mb-4">
                      <div class="card-body">
                        <h6 class="mb-4 text-primary"><i class="mdi mdi-account mr-2"></i>Dados Básicos</h6>
                        <div class="form-group">
                          <label>Nome Completo *</label>
                          <input type="text" name="nome" id="editar_nome" class="form-control" required>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group">
                              <label>Email *</label>
                              <input type="email" name="email" id="editar_email" class="form-control" required>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group">
                              <label>Nova Senha</label>
                              <input type="password" name="senha" class="form-control"
                                placeholder="Deixe em branco para manter">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card mb-4">
                      <div class="card-body">
                        <h6 class="mb-4 text-primary"><i class="mdi mdi-book-open-variant mr-2"></i>Atribuições</h6>
                        <div class="form-group">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <label>Disciplinas</label>
                            <div class="custom-control custom-checkbox">
                              <input type="checkbox" class="custom-control-input" id="checkPolivalenteEditar">
                              <label class="custom-control-label" for="checkPolivalenteEditar">Polivalente
                                (Todas)</label>
                            </div>
                          </div>
                          <select name="disciplinas[]" id="editar_disciplinas" class="form-control select2" multiple
                            style="width: 100%">
                            <?php foreach ($disciplinas as $d): ?>
                              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group">
                          <label>Turmas</label>
                          <select name="turmas[]" id="editar_turmas" class="form-control select2" multiple
                            style="width: 100%">
                            <?php foreach ($turmas as $t): ?>
                              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?> -
                                <?= $t['ano_letivo'] ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                    </div>

                    <div class="text-right">
                      <button type="button" class="btn btn-outline-secondary mr-2"
                        data-dismiss="modal">Cancelar</button>
                      <button type="submit" class="btn btn-gradient-primary">Salvar Alterações</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Excluir -->
          <div class="modal fade" id="modalExcluir" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title text-danger"><i class="mdi mdi-alert-circle mr-2"></i>Confirmar Exclusão</h5>
                  <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <p>Tem certeza que deseja excluir o professor <strong id="nomeProfessorModal"></strong>?</p>
                  <div class="alert alert-danger mt-3">
                    <i class="mdi mdi-alert-circle mr-1"></i> Esta ação é irreversível e removerá todos os dados
                    associados.
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                  <button type="button" class="btn btn-danger" id="confirmarExclusao">Excluir</button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo getAssetUrl("assets/vendors/select2/select2.min.js"); ?>"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>

  <script>
    $(document).ready(function () {
      // DataTable
      $('#tabelaProfessores').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json" },
        "columnDefs": [{ "orderable": false, "targets": 2 }]
      });

      // Select2 Defaults
      $.fn.select2.defaults.set("theme", "bootstrap");
      $.fn.select2.defaults.set("width", "100%");

      // Step Navigation
      let currentStep = 1;
      window.nextStep = function () {
        if (!validateStep(currentStep)) return;
        $('#step' + currentStep).removeClass('active');
        $('#step' + currentStep + '-indicator').addClass('completed').removeClass('active');
        currentStep++;
        $('#step' + currentStep).addClass('active');
        $('#step' + currentStep + '-indicator').addClass('active');
      };

      window.prevStep = function () {
        $('#step' + currentStep).removeClass('active');
        $('#step' + currentStep + '-indicator').removeClass('active');
        currentStep--;
        $('#step' + currentStep).addClass('active');
        $('#step' + currentStep + '-indicator').removeClass('completed').addClass('active');
      };

      function validateStep(step) {
        let valid = true;
        $('#step' + step + ' [required]').each(function () {
          if (!$(this).val()) {
            $(this).addClass('is-invalid');
            valid = false;
          } else {
            $(this).removeClass('is-invalid');
          }
        });

        if (step === 1) {
          const pass = $('input[name="senha"]').val();
          const confirm = $('input[name="confirmar_senha"]').val();
          if (pass !== confirm) {
            alert('As senhas não coincidem');
            valid = false;
          }
        }
        return valid;
      }

      // Modal Reset
      $('#modalCadastro').on('hidden.bs.modal', function () {
        $('#professorForm')[0].reset();
        currentStep = 1;
        $('.step-content').removeClass('active');
        $('#step1').addClass('active');
        $('.step').removeClass('active completed');
        $('#step1-indicator').addClass('active');
        $('.select2').val(null).trigger('change');
      });

      // Polivalente Logic
      function setupPolivalente(checkboxId, selectId) {
        $('#' + checkboxId).change(function () {
          const select = $('#' + selectId);
          if ($(this).is(':checked')) {
            select.find('option').prop('selected', true);
          } else {
            select.find('option').prop('selected', false);
          }
          select.trigger('change');
        });

        $('#' + selectId).change(function () {
          const total = $(this).find('option').length;
          const selected = $(this).find('option:selected').length;
          $('#' + checkboxId).prop('checked', total === selected && total > 0);
        });
      }

      setupPolivalente('checkPolivalenteCadastro', 'selectDisciplinasCadastro');
      setupPolivalente('checkPolivalenteEditar', 'editar_disciplinas');

      // Init Select2 in Modals
      $('.modal').on('shown.bs.modal', function () {
        $(this).find('.select2').select2({
          dropdownParent: $(this)
        });
      });

      // Edit Professor
      $('.btn-editar').click(function () {
        const id = $(this).data('id');
        $.get('buscar_professor.php', { id: id }, function (data) {
          const resp = JSON.parse(data);
          if (resp.success) {
            const p = resp.professor;
            $('#editar_professor_id').val(p.id);
            $('#editar_nome').val(p.nome);
            $('#editar_email').val(p.email);

            $('#modalEditar').modal('show');

            // Wait for modal to show to set select2 values
            setTimeout(() => {
              $('#editar_disciplinas').val(p.disciplinas).trigger('change');
              $('#editar_turmas').val(p.turmas).trigger('change');
            }, 100);
          } else {
            alert(resp.message);
          }
        });
      });

      // Delete Professor
      let deleteId = null;
      $('.btn-excluir').click(function () {
        deleteId = $(this).data('id');
        $('#nomeProfessorModal').text($(this).data('nome'));
        $('#modalExcluir').modal('show');
      });

      $('#confirmarExclusao').click(function () {
        if (deleteId) {
          const form = $('<form method="POST"></form>');
          form.append('<input type="hidden" name="excluir_professor" value="1">');
          form.append('<input type="hidden" name="professor_id" value="' + deleteId + '">');
          $('body').append(form);
          form.submit();
        }
      });
    });
  </script>
</body>

</html>