<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Buscar dados do aluno
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome, t.ano_letivo 
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch();
    
    if (!$aluno) {
        session_destroy();
        require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do aluno: " . $e->getMessage());
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// Processar geração da declaração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_declaracao = $_POST['tipo_declaracao'] ?? '';
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $finalidade = $_POST['finalidade'] ?? '';
    
    if ($tipo_declaracao) {
        // Armazenar dados na sessão para geração
        $_SESSION['declaracao_aluno'] = [
            'aluno' => $aluno,
            'tipo' => $tipo_declaracao,
            'data_emissao' => $data_emissao,
            'finalidade' => $finalidade
        ];
        
        require_once __DIR__ . '/../config/database.php';
        redirectTo('aluno/gerar_declaracao.php');
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Gerar Declarações - <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
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
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
</head>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <?php include 'partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.html -->
      <?php include 'partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Gerar Declarações </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gerar Declarações</li>
              </ol>
            </nav>
          </div>

          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Gerar Declaração</h4>
                  <p class="card-description">Selecione o tipo de declaração e preencha os dados necessários.</p>
                  
                  <form method="POST" class="forms-sample">
                    <div class="form-group">
                      <label for="tipo_declaracao">Tipo de Declaração *</label>
                      <select class="form-control" id="tipo_declaracao" name="tipo_declaracao" required>
                        <option value="">Selecione o tipo de declaração</option>
                        <option value="vinculo">Declaração de Vínculo Escolar</option>
                        <option value="matricula">Declaração de Matrícula</option>
                        <option value="frequencia">Declaração de Frequência</option>
                        <option value="transferencia">Declaração para Transferência</option>
                        <option value="programa_social">Declaração para Programa Social</option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="data_emissao">Data de Emissão</label>
                      <input type="date" class="form-control" id="data_emissao" name="data_emissao" 
                             value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                      <label for="finalidade">Finalidade da Declaração</label>
                      <textarea class="form-control" id="finalidade" name="finalidade" rows="3" 
                                placeholder="Ex: Para fins de matrícula, comprovação de vínculo escolar, inscrição em programa social, etc."></textarea>
                    </div>

                    <button type="submit" class="btn btn-gradient-primary me-2">Gerar Declaração</button>
                    <button type="reset" class="btn btn-light">Limpar Formulário</button>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Informações sobre Declarações -->
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Tipos de Declarações Disponíveis</h4>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="border-bottom pb-3 mb-3">
                        <h5><i class="mdi mdi-certificate text-primary"></i> Declaração de Vínculo Escolar</h5>
                        <p class="text-muted">Comprova que o aluno está regularmente matriculado na instituição de ensino.</p>
                        <small class="text-muted">Usado para: Comprovação de matrícula, inscrição em programas sociais, documentação para órgãos públicos.</small>
                      </div>
                      
                      <div class="border-bottom pb-3 mb-3">
                        <h5><i class="mdi mdi-file-document text-success"></i> Declaração de Matrícula</h5>
                        <p class="text-muted">Confirma a matrícula do aluno no ano letivo vigente.</p>
                        <small class="text-muted">Usado para: Transferência escolar, comprovação de matrícula ativa.</small>
                      </div>
                      
                      <div class="border-bottom pb-3 mb-3">
                        <h5><i class="mdi mdi-calendar-check text-info"></i> Declaração de Frequência</h5>
                        <p class="text-muted">Atesta a frequência escolar do aluno.</p>
                        <small class="text-muted">Usado para: Programas sociais, bolsas de estudo, comprovação de frequência.</small>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="border-bottom pb-3 mb-3">
                        <h5><i class="mdi mdi-school text-warning"></i> Declaração para Transferência</h5>
                        <p class="text-muted">Documento específico para transferência escolar.</p>
                        <small class="text-muted">Usado para: Processo de transferência entre escolas.</small>
                      </div>
                      
                      <div class="border-bottom pb-3 mb-3">
                        <h5><i class="mdi mdi-heart text-danger"></i> Declaração para Programa Social</h5>
                        <p class="text-muted">Declaração específica para inscrição em programas sociais.</p>
                        <small class="text-muted">Usado para: Bolsa Família, outros programas governamentais.</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Histórico de Declarações -->
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Minhas Declarações Geradas</h4>
                  <div class="alert alert-info">
                    <i class="mdi mdi-information"></i> 
                    <strong>Funcionalidade em desenvolvimento:</strong> Em breve você poderá visualizar o histórico de declarações geradas.
                  </div>
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
    $(document).ready(function() {
      // Inicializar Select2
      $('#tipo_declaracao').select2({
        theme: 'bootstrap',
        placeholder: 'Selecione o tipo de declaração'
      });
    });
  </script>
</body>

</html>
