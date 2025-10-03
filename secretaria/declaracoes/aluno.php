<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// Buscar alunos disponíveis
$alunos = [];
try {
    $stmt = $pdo->query("
        SELECT a.id, a.nome_completo, a.nome, t.nome as turma_nome, t.ano_letivo 
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        ORDER BY a.nome_completo, a.nome
    ");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar alunos: " . $e->getMessage());
}

// Processar geração da declaração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aluno_id = $_POST['aluno_id'] ?? '';
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $finalidade = $_POST['finalidade'] ?? '';
    
    if ($aluno_id) {
        try {
            // Buscar dados do aluno
            $stmt = $pdo->prepare("
                SELECT a.*, t.nome as turma_nome, t.ano_letivo 
                FROM alunos a 
                LEFT JOIN turmas t ON a.turma_id = t.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$aluno_id]);
            $aluno = $stmt->fetch();
            
            if ($aluno) {
                // Gerar declaração
                $_SESSION['declaracao_vinculo'] = [
                    'aluno' => $aluno,
                    'data_emissao' => $data_emissao,
                    'finalidade' => $finalidade
                ];
                header('Location: gerar_declaracao_vinculo.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar dados do aluno: " . $e->getMessage());
            $erro = "Erro ao processar dados do aluno.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Declaração de Vínculo - Aluno</title>
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
            <h3 class="page-title"> Declaração de Vínculo - Aluno </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Declarações</a></li>
                <li class="breadcrumb-item active" aria-current="page">Vínculo - Aluno</li>
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

          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Gerar Declaração de Vínculo</h4>
                  <p class="card-description">Selecione o aluno e preencha os dados para gerar a declaração de vínculo escolar.</p>
                  
                  <form method="POST" class="forms-sample">
                    <div class="form-group">
                      <label for="aluno_id">Aluno *</label>
                      <select class="form-control" id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione um aluno</option>
                        <?php foreach ($alunos as $aluno): ?>
                          <option value="<?= $aluno['id'] ?>">
                            <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?> 
                            - <?= htmlspecialchars($aluno['turma_nome'] ?? 'Sem turma') ?> 
                            (<?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?>)
                          </option>
                        <?php endforeach; ?>
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
                                placeholder="Ex: Para fins de matrícula, comprovação de vínculo escolar, etc."></textarea>
                    </div>

                    <button type="submit" class="btn btn-gradient-primary me-2">Gerar Declaração</button>
                    <button type="reset" class="btn btn-light">Limpar Formulário</button>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Informações sobre a declaração -->
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Sobre a Declaração de Vínculo</h4>
                  <div class="row">
                    <div class="col-md-6">
                      <h5><i class="mdi mdi-information-outline text-info"></i> O que é?</h5>
                      <p>A declaração de vínculo escolar é um documento oficial que comprova que o aluno está regularmente matriculado na instituição de ensino.</p>
                    </div>
                    <div class="col-md-6">
                      <h5><i class="mdi mdi-check-circle text-success"></i> Para que serve?</h5>
                      <ul>
                        <li>Comprovação de matrícula</li>
                        <li>Inscrição em programas sociais</li>
                        <li>Documentação para órgãos públicos</li>
                        <li>Transferência escolar</li>
                        <li>Outros fins administrativos</li>
                      </ul>
                    </div>
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
    $(document).ready(function() {
      // Inicializar Select2 para melhor UX
      $('#aluno_id').select2({
        theme: 'bootstrap',
        placeholder: 'Selecione um aluno',
        allowClear: true
      });
    });
  </script>
</body>

</html>
