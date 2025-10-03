<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: ../../login.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Processar exclusão de professor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_professor'])) {
    $professor_id = $_POST['professor_id'];
    
    // Debug: verificar se os dados estão chegando
    error_log("Tentativa de exclusão - Professor ID: " . $professor_id);
    
    try {
        $pdo->beginTransaction();
        
        // Remover planos de aula do professor (se a tabela existir)
        try {
            $stmt = $pdo->prepare("DELETE FROM planos_aula WHERE professor_id = ?");
            $stmt->execute([$professor_id]);
            $planos_removidos = $stmt->rowCount();
            error_log("Planos de aula removidos para professor ID: " . $professor_id . " (total: " . $planos_removidos . ")");
        } catch (PDOException $e) {
            error_log("Tabela planos_aula não existe ou erro: " . $e->getMessage());
        }
        
        // Remover notas do professor (se a tabela existir)
        try {
            $stmt = $pdo->prepare("DELETE FROM notas WHERE professor_id = ?");
            $stmt->execute([$professor_id]);
            $notas_removidas = $stmt->rowCount();
            error_log("Notas removidas para professor ID: " . $professor_id . " (total: " . $notas_removidas . ")");
        } catch (PDOException $e) {
            error_log("Tabela notas não existe ou erro: " . $e->getMessage());
        }
        
        // Remover pareceres do professor (se a tabela existir)
        try {
            $stmt = $pdo->prepare("DELETE FROM pareceres WHERE professor_id = ?");
            $stmt->execute([$professor_id]);
            $pareceres_removidos = $stmt->rowCount();
            error_log("Pareceres removidos para professor ID: " . $professor_id . " (total: " . $pareceres_removidos . ")");
        } catch (PDOException $e) {
            error_log("Tabela pareceres não existe ou erro: " . $e->getMessage());
        }
        
        // Remover associações com disciplinas (se a tabela existir)
        try {
            $stmt = $pdo->prepare("DELETE FROM professores_disciplinas WHERE professor_id = ?");
            $stmt->execute([$professor_id]);
            error_log("Associações com disciplinas removidas para professor ID: " . $professor_id);
        } catch (PDOException $e) {
            error_log("Tabela professores_disciplinas não existe ou erro: " . $e->getMessage());
        }
        
        // Remover associações com turmas (se a tabela existir)
        try {
            $stmt = $pdo->prepare("DELETE FROM professores_turmas WHERE professor_id = ?");
            $stmt->execute([$professor_id]);
            error_log("Associações com turmas removidas para professor ID: " . $professor_id);
        } catch (PDOException $e) {
            error_log("Tabela professores_turmas não existe ou erro: " . $e->getMessage());
        }
        
        // Remover o professor
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'professor'");
        $resultado = $stmt->execute([$professor_id]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            $mensagem = "Professor excluído com sucesso! Todos os dados relacionados foram removidos.";
            $tipo_mensagem = "success";
            error_log("Professor ID " . $professor_id . " excluído com sucesso");
        } else {
            $pdo->rollBack();
            $mensagem = "Professor não encontrado ou já foi excluído.";
            $tipo_mensagem = "warning";
            error_log("Professor ID " . $professor_id . " não encontrado para exclusão");
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao excluir professor: " . $e->getMessage();
        $tipo_mensagem = "danger";
        error_log("Erro ao excluir professor ID " . $professor_id . ": " . $e->getMessage());
    }
}

// Buscar disciplinas disponíveis
$disciplinas = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome");
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar disciplinas: " . $e->getMessage());
}

// Buscar turmas disponíveis
$turmas = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());
}

// Buscar professores cadastrados
$professores = [];
try {
    // Buscar apenas os professores básicos primeiro
    $stmt = $pdo->query("
        SELECT id, nome, email
        FROM usuarios 
        WHERE tipo = 'professor'
        ORDER BY nome
    ");
    $professores_basicos = $stmt->fetchAll();
    
    // Para cada professor, buscar suas disciplinas e turmas (com tratamento de erro)
    foreach ($professores_basicos as $professor) {
        $disciplinas_professor = [];
        $turmas_professor = [];
        
        // Buscar disciplinas do professor (com tratamento de erro)
        try {
            $stmt = $pdo->prepare("
                SELECT d.nome 
                FROM disciplinas d
                INNER JOIN professores_disciplinas pd ON d.id = pd.disciplina_id
                WHERE pd.professor_id = ?
                ORDER BY d.nome
            ");
            $stmt->execute([$professor['id']]);
            $disciplinas_professor = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Se a tabela não existir ou der erro, continuar com array vazio
            error_log("Erro ao buscar disciplinas do professor {$professor['id']}: " . $e->getMessage());
        }
        
        // Buscar turmas do professor (com tratamento de erro)
        try {
            $stmt = $pdo->prepare("
                SELECT t.nome 
                FROM turmas t
                INNER JOIN professores_turmas pt ON t.id = pt.turma_id
                WHERE pt.professor_id = ?
                ORDER BY t.nome
            ");
            $stmt->execute([$professor['id']]);
            $turmas_professor = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Se a tabela não existir ou der erro, continuar com array vazio
            error_log("Erro ao buscar turmas do professor {$professor['id']}: " . $e->getMessage());
        }
        
        // Adicionar ao array de professores
        $professores[] = [
            'id' => $professor['id'],
            'nome' => $professor['nome'],
            'email' => $professor['email'],
            'disciplinas' => implode(', ', $disciplinas_professor),
            'turmas' => implode(', ', $turmas_professor)
        ];
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar professores: " . $e->getMessage());
    $mensagem = "Erro ao buscar professores: " . $e->getMessage();
    $tipo_mensagem = "danger";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cadastrar Professor</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../../assets/vendors/select2/select2.min.css">
  <link rel="stylesheet" href="../../assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
  <!-- DataTables CSS via CDN -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
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
    .select2-container {
      width: 100% !important;
    }
    .table td {
      vertical-align: middle;
    }
    .btn-group .btn {
      margin-right: 2px;
    }
    .btn-group .btn:last-child {
      margin-right: 0;
    }
    .badge {
      font-size: 0.75rem;
    }
    .table tbody tr:hover {
      background-color: rgba(0,0,0,0.05);
    }
    
    /* Estilos para a modal de exclusão */
    .modal-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
    }
    
    .modal-title {
      color: #dc3545;
      font-weight: 600;
    }
    
    .modal-body .alert {
      border-left: 4px solid #dc3545;
    }
    
    .modal-body .alert-danger {
      background-color: #f8d7da;
      border-color: #f5c6cb;
      color: #721c24;
    }
    
    .modal-footer .btn-danger {
      background-color: #dc3545;
      border-color: #dc3545;
    }
    
    .modal-footer .btn-danger:hover {
      background-color: #c82333;
      border-color: #bd2130;
    }
    
    .modal-footer .btn-danger:disabled {
      background-color: #6c757d;
      border-color: #6c757d;
    }
    
    /* Estilos personalizados para o modal de edição */
    .modal-xl {
      max-width: 900px;
    }
    
    .modal-content {
      animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    .modal-header {
      position: relative;
      overflow: hidden;
    }
    
    .modal-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
      pointer-events: none;
    }
    
    .form-control:focus {
      border-color: #667eea !important;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
    }
    
    .select2-container--bootstrap .select2-selection {
      border: 2px solid #e9ecef !important;
      border-radius: 8px !important;
      min-height: 45px !important;
      padding: 8px 12px !important;
    }
    
    .select2-container--bootstrap .select2-selection:focus {
      border-color: #667eea !important;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
    }
    
    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      border: none !important;
      border-radius: 15px !important;
      color: white !important;
      padding: 4px 12px !important;
      margin: 2px !important;
    }
    
    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice__remove {
      color: white !important;
      margin-right: 5px !important;
    }
    
    .select2-container--bootstrap .select2-selection--multiple .select2-selection__choice__remove:hover {
      color: #ff6b6b !important;
    }
    
    .card {
      transition: all 0.3s ease;
    }
    
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
    }
    
    .btn {
      transition: all 0.3s ease;
    }
    
    .btn:hover {
      transform: translateY(-1px);
    }
    
    .btn:active {
      transform: translateY(0);
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
            <h3 class="page-title"> Gerenciar Professores </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Professores</li>
              </ol>
            </nav>
          </div>

          <!-- Mensagens de Feedback -->
          <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
              <i class="mdi mdi-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
              <?= htmlspecialchars($mensagem) ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
          <?php endif; ?>

          <!-- Tabela de Professores -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">
                      <i class="mdi mdi-account-multiple"></i> Professores Cadastrados
                    </h4>
                    <button type="button" class="btn btn-gradient-primary" onclick="toggleForm()">
                      <i class="mdi mdi-plus"></i> Novo Professor
                    </button>
                  </div>
                  
                  <?php if (empty($professores)): ?>
                    <div class="text-center py-5">
                      <i class="mdi mdi-account-multiple-outline" style="font-size: 64px; color: #ccc;"></i>
                      <h5 class="mt-3 text-muted">Nenhum professor cadastrado</h5>
                      <p class="text-muted">Clique em "Novo Professor" para começar.</p>
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
                                <?php if (!empty($professor['disciplinas'])): ?>
                                  <span class="badge badge-info"><?= htmlspecialchars($professor['disciplinas']) ?></span>
                                <?php else: ?>
                                  <span class="text-muted">Nenhuma</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <div class="btn-group" role="group">
                                  <button type="button" class="btn btn-outline-primary btn-sm btn-editar" 
                                          data-id="<?= $professor['id'] ?>" 
                                          title="Editar">
                                    <i class="mdi mdi-pencil"></i>
                                  </button>
                                  <button type="button" class="btn btn-outline-danger btn-sm btn-excluir" 
                                          data-id="<?= $professor['id'] ?>" 
                                          data-nome="<?= htmlspecialchars($professor['nome']) ?>" 
                                          title="Excluir">
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

          <!-- Formulário de Cadastro (inicialmente oculto) -->
          <div class="row" id="formularioCadastro" style="display: none;">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">
                      <i class="mdi mdi-account-plus"></i> Cadastrar Novo Professor
                    </h4>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleForm()">
                      <i class="mdi mdi-close"></i> Cancelar
                    </button>
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
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php unset($_SESSION['erro_cadastro']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['sucesso_cadastro'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $_SESSION['sucesso_cadastro'] ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php unset($_SESSION['sucesso_cadastro']); ?>
          <?php endif; ?>

          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <form id="professorForm" method="POST" action="salvar_professor.php" class="forms-sample">
                    
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

                     <!-- ETAPA 2: Disciplinas e Turmas -->
                     <div class="step-content" id="step2">
                       <h4 class="mb-4">Etapa 2: Disciplinas e Turmas</h4>
                       
                       <div class="form-group">
                         <label>Disciplinas que leciona *</label>
                         <select name="disciplinas[]" class="form-control select2" multiple required>
                           <?php foreach ($disciplinas as $disciplina): ?>
                             <option value="<?= $disciplina['id'] ?>"><?= htmlspecialchars($disciplina['nome']) ?></option>
                           <?php endforeach; ?>
                         </select>
                         <small class="form-text text-muted">Pressione Ctrl (ou Cmd no Mac) para selecionar múltiplas disciplinas</small>
                       </div>

                       <div class="form-group">
                         <label>Turmas que leciona *</label>
                         <select name="turmas[]" class="form-control select2" multiple required>
                           <?php foreach ($turmas as $turma): ?>
                             <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
                           <?php endforeach; ?>
                         </select>
                         <small class="form-text text-muted">Pressione Ctrl (ou Cmd no Mac) para selecionar múltiplas turmas</small>
                       </div>

                       <div class="text-right mt-4">
                         <button type="button" class="btn btn-secondary" onclick="prevStep()">Etapa Anterior</button>
                         <button type="submit" class="btn btn-gradient-primary">Cadastrar Professor</button>
                       </div>
                     </div>

                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- Modal de Edição -->
  <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0; padding: 25px 30px; border: none;">
          <div class="d-flex align-items-center">
            <div class="mr-3">
              <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="mdi mdi-account-edit" style="font-size: 24px;"></i>
              </div>
            </div>
            <div>
              <h4 class="modal-title mb-0" id="modalEditarLabel" style="font-weight: 600; font-size: 1.5rem;">
                Editar Professor
              </h4>
              <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem;">Atualize as informações do professor</p>
            </div>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8; font-size: 1.5rem;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" style="padding: 30px; background: #f8f9fa;">
          <form id="formEditar" method="POST" action="editar_professor.php">
            <input type="hidden" name="professor_id" id="editar_professor_id">
            
            <!-- Card de Dados Básicos -->
            <div class="card mb-4" style="border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
              <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; border: none;">
                <h6 class="mb-0" style="font-weight: 600;">
                  <i class="mdi mdi-account mr-2"></i>Dados Básicos
                </h6>
              </div>
              <div class="card-body" style="padding: 25px;">
                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                        <i class="mdi mdi-account mr-1"></i>Nome completo *
                      </label>
                      <input type="text" name="nome" id="editar_nome" class="form-control" required 
                             style="border-radius: 8px; border: 2px solid #e9ecef; padding: 12px 15px; font-size: 0.95rem; transition: all 0.3s ease;"
                             onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 0.2rem rgba(102, 126, 234, 0.25)'"
                             onblur="this.style.borderColor='#e9ecef'; this.style.boxShadow='none'">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                        <i class="mdi mdi-email mr-1"></i>Email *
                      </label>
                      <input type="email" name="email" id="editar_email" class="form-control" required
                             style="border-radius: 8px; border: 2px solid #e9ecef; padding: 12px 15px; font-size: 0.95rem; transition: all 0.3s ease;"
                             onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 0.2rem rgba(102, 126, 234, 0.25)'"
                             onblur="this.style.borderColor='#e9ecef'; this.style.boxShadow='none'">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                        <i class="mdi mdi-lock mr-1"></i>Nova Senha
                      </label>
                      <input type="password" name="senha" id="editar_senha" class="form-control"
                             style="border-radius: 8px; border: 2px solid #e9ecef; padding: 12px 15px; font-size: 0.95rem; transition: all 0.3s ease;"
                             onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 0.2rem rgba(102, 126, 234, 0.25)'"
                             onblur="this.style.borderColor='#e9ecef'; this.style.boxShadow='none'">
                      <small class="form-text" style="color: #6c757d; font-size: 0.85rem; margin-top: 5px;">
                        <i class="mdi mdi-information-outline mr-1"></i>Deixe em branco para manter a senha atual
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card de Disciplinas e Turmas -->
            <div class="card" style="border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
              <div class="card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; border: none;">
                <h6 class="mb-0" style="font-weight: 600;">
                  <i class="mdi mdi-book-multiple mr-2"></i>Disciplinas e Turmas
                </h6>
              </div>
              <div class="card-body" style="padding: 25px;">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                        <i class="mdi mdi-book mr-1"></i>Disciplinas que leciona *
                      </label>
                      <select name="disciplinas[]" id="editar_disciplinas" class="form-control select2" multiple required
                              style="border-radius: 8px; border: 2px solid #e9ecef;">
                        <?php foreach ($disciplinas as $disciplina): ?>
                          <option value="<?= $disciplina['id'] ?>"><?= htmlspecialchars($disciplina['nome']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                        <i class="mdi mdi-school mr-1"></i>Turmas que leciona *
                      </label>
                      <select name="turmas[]" id="editar_turmas" class="form-control select2" multiple required
                              style="border-radius: 8px; border: 2px solid #e9ecef;">
                        <?php foreach ($turmas as $turma): ?>
                          <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer" style="background: white; border-radius: 0 0 15px 15px; padding: 20px 30px; border: none; border-top: 1px solid #e9ecef;">
          <button type="button" class="btn btn-light" data-dismiss="modal" 
                  style="border-radius: 8px; padding: 10px 25px; font-weight: 600; border: 2px solid #e9ecef; color: #6c757d; transition: all 0.3s ease;"
                  onmouseover="this.style.borderColor='#dee2e6'; this.style.backgroundColor='#f8f9fa'"
                  onmouseout="this.style.borderColor='#e9ecef'; this.style.backgroundColor='white'">
            <i class="mdi mdi-close mr-2"></i>Cancelar
          </button>
          <button type="button" class="btn btn-gradient-primary" id="salvarEdicao"
                  style="border-radius: 8px; padding: 10px 25px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; transition: all 0.3s ease;"
                  onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(102, 126, 234, 0.4)'"
                  onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <i class="mdi mdi-content-save mr-2"></i>Salvar Alterações
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmação de Exclusão -->
  <div class="modal fade" id="modalExcluir" tabindex="-1" role="dialog" aria-labelledby="modalExcluirLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalExcluirLabel">
            <i class="mdi mdi-alert-circle text-danger"></i> Confirmar Exclusão
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Tem certeza que deseja excluir o professor <strong id="nomeProfessorModal"></strong>?</p>
          <p class="text-muted">Esta ação não pode ser desfeita e removerá:</p>
          <ul class="text-muted">
            <li>Todos os planos de aula do professor</li>
            <li>Todas as notas lançadas pelo professor</li>
            <li>Todos os pareceres pedagógicos do professor</li>
            <li>Associações com disciplinas e turmas</li>
          </ul>
          <div class="alert alert-danger" role="alert">
            <i class="mdi mdi-alert-circle"></i>
            <strong>Atenção:</strong> Esta operação é irreversível e removerá TODOS os dados relacionados ao professor!
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="mdi mdi-close"></i> Cancelar
          </button>
          <button type="button" class="btn btn-danger" id="confirmarExclusao">
            <i class="mdi mdi-delete"></i> Excluir Professor
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery primeiro -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- plugins:js -->
  <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Plugin js for this page -->
  <script src="../../assets/vendors/select2/select2.min.js"></script>
  <script src="../../assets/vendors/typeahead.js/typeahead.bundle.min.js"></script>
  <!-- DataTables via CDN -->
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
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

     // Teste básico do jQuery
     console.log('jQuery carregado:', typeof $ !== 'undefined');
     if (typeof $ !== 'undefined') {
       console.log('jQuery versão:', $.fn.jquery);
     }
     
     
     // Inicializar quando o documento estiver pronto
     $(document).ready(function() {
       console.log('Documento carregado, inicializando...');
       
       // Inicializar Select2 apenas para o formulário de cadastro
       if (typeof $.fn.select2 !== 'undefined') {
         $('.select2').not('#editar_disciplinas, #editar_turmas').select2({
           theme: 'bootstrap'
         });
         console.log('Select2 inicializado para formulário de cadastro');
       } else {
         console.log('Select2 não disponível');
       }
       
       // Inicializar DataTable (se disponível)
       if (typeof $.fn.DataTable !== 'undefined') {
         console.log('DataTable disponível, inicializando...');
         $('#tabelaProfessores').DataTable({
           "language": {
             "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
           },
           "pageLength": 10,
           "order": [[0, "asc"]],
           "columnDefs": [
             { "orderable": false, "targets": 2 } // Desabilitar ordenação na coluna de ações
           ],
           "responsive": true
         });
         console.log('DataTable inicializado com sucesso');
       } else {
         console.log('DataTable não disponível, usando tabela simples');
       }
       
       // Event listeners para os botões usando jQuery
       console.log('Configurando event listeners...');
       
       $(document).on('click', '.btn-editar', function(e) {
         e.preventDefault();
         const id = $(this).data('id');
         console.log('Botão editar clicado - ID:', id);
         
         // Carregar dados do professor via AJAX
         $.ajax({
           url: 'buscar_professor.php',
           method: 'GET',
           data: { id: id },
           dataType: 'json',
           success: function(response) {
             if (response.success) {
               // Preencher formulário de edição
               $('#editar_professor_id').val(response.professor.id);
               $('#editar_nome').val(response.professor.nome);
               $('#editar_email').val(response.professor.email);
               
               // Mostrar modal primeiro
               $('#modalEditar').modal('show');
               
               // Aguardar o modal ser completamente exibido
               $('#modalEditar').on('shown.bs.modal', function() {
                 console.log('Modal aberto, inicializando Select2...');
                 
                 // Destruir Select2 existente se houver
                 if ($('#editar_disciplinas').hasClass('select2-hidden-accessible')) {
                   console.log('Destruindo Select2 de disciplinas existente');
                   $('#editar_disciplinas').select2('destroy');
                 }
                 if ($('#editar_turmas').hasClass('select2-hidden-accessible')) {
                   console.log('Destruindo Select2 de turmas existente');
                   $('#editar_turmas').select2('destroy');
                 }
                 
                 // Aguardar um pouco para garantir que o DOM esteja pronto
                 setTimeout(function() {
                   console.log('Inicializando Select2 de disciplinas...');
                   
                   // Verificar se o elemento existe
                   if ($('#editar_disciplinas').length === 0) {
                     console.error('Elemento #editar_disciplinas não encontrado');
                     return;
                   }
                   
                   // Inicializar Select2 de disciplinas
                   $('#editar_disciplinas').select2({
                     theme: 'bootstrap',
                     placeholder: 'Selecione as disciplinas',
                     allowClear: true,
                     width: '100%',
                     dropdownParent: $('#modalEditar')
                   });
                   
                   console.log('Select2 de disciplinas inicializado');
                   
                   // Inicializar Select2 de turmas
                   console.log('Inicializando Select2 de turmas...');
                   $('#editar_turmas').select2({
                     theme: 'bootstrap',
                     placeholder: 'Selecione as turmas',
                     allowClear: true,
                     width: '100%',
                     dropdownParent: $('#modalEditar')
                   });
                   
                   console.log('Select2 de turmas inicializado');
                   
                   // Aguardar um pouco mais para garantir que o Select2 esteja pronto
                   setTimeout(function() {
                     console.log('Preenchendo dados...');
                     
                     // Preencher disciplinas após inicializar Select2
                     if (response.professor.disciplinas && response.professor.disciplinas.length > 0) {
                       console.log('Preenchendo disciplinas:', response.professor.disciplinas);
                       $('#editar_disciplinas').val(response.professor.disciplinas).trigger('change');
                     }
                     
                     // Preencher turmas após inicializar Select2
                     if (response.professor.turmas && response.professor.turmas.length > 0) {
                       console.log('Preenchendo turmas:', response.professor.turmas);
                       $('#editar_turmas').val(response.professor.turmas).trigger('change');
                     }
                     
                     console.log('Dados preenchidos com sucesso');
                   }, 200);
                   
                 }, 100);
                 
                 // Remover o event listener para evitar múltiplas inicializações
                 $('#modalEditar').off('shown.bs.modal');
               });
               
             } else {
               alert('Erro ao carregar dados do professor: ' + response.message);
             }
           },
           error: function() {
             alert('Erro ao carregar dados do professor. Tente novamente.');
           }
         });
       });
       
       // Variáveis para armazenar dados do professor a ser excluído
       let professorParaExcluir = null;
       
       $(document).on('click', '.btn-excluir', function(e) {
         e.preventDefault();
         console.log('Botão excluir clicado!');
         
         const id = $(this).data('id');
         const nome = $(this).data('nome');
         
         console.log('Excluir professor ID:', id, 'Nome:', nome);
         
         // Armazenar dados do professor
         professorParaExcluir = { id: id, nome: nome };
         
         // Atualizar modal com dados do professor
         $('#nomeProfessorModal').text(nome);
         
         // Mostrar modal
         $('#modalExcluir').modal('show');
       });
       
       // Event listener para o botão de confirmação na modal
       $('#confirmarExclusao').on('click', function() {
         if (professorParaExcluir) {
           console.log('Usuário confirmou exclusão via modal');
           
           // Desabilitar botão e mostrar loading
           $(this).prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Excluindo...');
           
           // Criar formulário temporário para envio
           const form = $('<form>').attr({
             method: 'POST',
             action: window.location.href
           });
           
           form.append($('<input>').attr({
             type: 'hidden',
             name: 'professor_id',
             value: professorParaExcluir.id
           }));
           
           form.append($('<input>').attr({
             type: 'hidden',
             name: 'excluir_professor',
             value: '1'
           }));
           
           console.log('Enviando formulário...');
           $('body').append(form);
           form.submit();
         }
       });
       
       // Event listener para salvar edição
       $('#salvarEdicao').on('click', function() {
         const form = $('#formEditar');
         const formData = form.serialize();
         
         // Validar formulário
         if (!form[0].checkValidity()) {
           form[0].reportValidity();
           return;
         }
         
         // Desabilitar botão e mostrar loading
         $(this).prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Salvando...');
         
         $.ajax({
           url: 'editar_professor.php',
           method: 'POST',
           data: formData,
           dataType: 'json',
           success: function(response) {
             if (response.success) {
               // Fechar modal
               $('#modalEditar').modal('hide');
               // Recarregar página para mostrar alterações
               location.reload();
             } else {
               alert('Erro ao salvar alterações: ' + response.message);
               $('#salvarEdicao').prop('disabled', false).html('<i class="mdi mdi-content-save"></i> Salvar Alterações');
             }
           },
           error: function() {
             alert('Erro ao salvar alterações. Tente novamente.');
             $('#salvarEdicao').prop('disabled', false).html('<i class="mdi mdi-content-save"></i> Salvar Alterações');
           }
         });
       });
       
       // Resetar modal quando fechada
       $('#modalExcluir').on('hidden.bs.modal', function() {
         professorParaExcluir = null;
         $('#confirmarExclusao').prop('disabled', false).html('<i class="mdi mdi-delete"></i> Excluir Professor');
       });
       
       // Resetar modal de edição quando fechada
       $('#modalEditar').on('hidden.bs.modal', function() {
         // Destruir Select2 antes de resetar
         if ($('#editar_disciplinas').hasClass('select2-hidden-accessible')) {
           $('#editar_disciplinas').select2('destroy');
         }
         if ($('#editar_turmas').hasClass('select2-hidden-accessible')) {
           $('#editar_turmas').select2('destroy');
         }
         
         // Resetar formulário
         $('#formEditar')[0].reset();
         
         // Reinicializar Select2
         $('#editar_disciplinas').select2({
           theme: 'bootstrap',
           placeholder: 'Selecione as disciplinas',
           allowClear: true,
           width: '100%',
           dropdownParent: $('#modalEditar')
         });
         
         $('#editar_turmas').select2({
           theme: 'bootstrap',
           placeholder: 'Selecione as turmas',
           allowClear: true,
           width: '100%',
           dropdownParent: $('#modalEditar')
         });
         
         // Resetar botão
         $('#salvarEdicao').prop('disabled', false).html('<i class="mdi mdi-content-save"></i> Salvar Alterações');
       });
     });

     // Função para alternar formulário
     function toggleForm() {
       const formulario = $('#formularioCadastro');
       if (formulario.is(':hidden')) {
         formulario.show();
         formulario[0].scrollIntoView({ behavior: 'smooth' });
       } else {
         formulario.hide();
       }
     }

  </script>
</body>

</html>
