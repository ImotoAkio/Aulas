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

// Buscar turmas
$turmas = [];
try {
    $stmt = $pdo->query("
        SELECT t.*, 
               COUNT(a.id) as total_alunos,
               COUNT(CASE WHEN a.nome_completo IS NOT NULL AND a.nome_completo != '' THEN 1 END) as alunos_completos
        FROM turmas t 
        LEFT JOIN alunos a ON t.id = a.turma_id 
        GROUP BY t.id 
        ORDER BY t.nome
    ");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());
}

// Estatísticas
$total_turmas = count($turmas);
$turmas_com_alunos = 0;
$total_alunos = 0;

foreach ($turmas as $turma) {
    if ($turma['total_alunos'] > 0) {
        $turmas_com_alunos++;
        $total_alunos += $turma['total_alunos'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Gerenciar Turmas - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <!-- endinject -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>" />
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
            <h3 class="page-title">Gerenciar Turmas</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href='<?php echo getPageUrl("secretaria/index.php"); ?>'>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Turmas</li>
              </ol>
            </nav>
          </div>

          <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="mdi mdi-check-circle"></i>
              <?= htmlspecialchars($_SESSION['sucesso']) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['sucesso']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="mdi mdi-alert-circle"></i>
              <?= htmlspecialchars($_SESSION['erro']) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['erro']); ?>
          <?php endif; ?>

          <!-- Estatísticas -->
          <div class="row">
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $total_turmas ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
                        <span class="mdi mdi-school icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Total de Turmas</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $turmas_com_alunos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-account-multiple icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Turmas com Alunos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $total_alunos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-warning">
                        <span class="mdi mdi-account icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Total de Alunos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $total_turmas > 0 ? round(($turmas_com_alunos / $total_turmas) * 100, 1) : 0 ?>%</h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
                        <span class="mdi mdi-percent icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Taxa de Ocupação</h6>
                </div>
              </div>
            </div>
          </div>

          <!-- Ações -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                      <h4 class="card-title">Lista de Turmas</h4>
                      <p class="card-description">Gerencie as turmas do sistema. Clique em "Adicionar" para criar uma nova turma.</p>
                    </div>
                    <div>
                      <a href="adicionar_turma.php" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Adicionar Turma
                      </a>
                    </div>
                  </div>
                  
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Nome</th>
                          <th>Ano Letivo</th>
                          <th>Alunos</th>
                          <th>Status</th>
                          <th>Ações</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($turmas)): ?>
                          <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                              <i class="mdi mdi-school" style="font-size: 48px; opacity: 0.3;"></i>
                              <br><br>
                              Nenhuma turma cadastrada
                            </td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($turmas as $turma): ?>
                            <tr>
                              <td>
                                <div class="d-flex align-items-center">
                                  <div class="mr-3">
                                    <i class="mdi mdi-school text-primary" style="font-size: 24px;"></i>
                                  </div>
                                  <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($turma['nome']) ?></h6>
                                    <small class="text-muted">ID: <?= $turma['id'] ?></small>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <span class="badge badge-info"><?= htmlspecialchars($turma['ano_letivo']) ?></span>
                              </td>
                              <td>
                                <div>
                                  <span class="font-weight-bold"><?= $turma['total_alunos'] ?></span> aluno(s)
                                  <?php if ($turma['total_alunos'] > 0): ?>
                                    <br><small class="text-muted"><?= $turma['alunos_completos'] ?> com cadastro completo</small>
                                  <?php endif; ?>
                                </div>
                              </td>
                              <td>
                                <?php if ($turma['total_alunos'] > 0): ?>
                                  <span class="badge badge-success">
                                    <i class="mdi mdi-check-circle"></i> Ativa
                                  </span>
                                <?php else: ?>
                                  <span class="badge badge-secondary">
                                    <i class="mdi mdi-pause-circle"></i> Vazia
                                  </span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <div class="btn-group" role="group">
                                  <a href="editar_turma.php?id=<?= $turma['id'] ?>" 
                                     class="btn btn-outline-primary btn-sm" 
                                     title="Editar">
                                    <i class="mdi mdi-pencil"></i>
                                  </a>
                                  <?php if ($turma['total_alunos'] == 0): ?>
                                    <a href="excluir_turma.php?id=<?= $turma['id'] ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       title="Excluir"
                                       onclick="return confirm('Tem certeza que deseja excluir esta turma?')">
                                      <i class="mdi mdi-delete"></i>
                                    </a>
                                  <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm" 
                                            title="Não é possível excluir turma com alunos"
                                            disabled>
                                      <i class="mdi mdi-delete"></i>
                                    </button>
                                  <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
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
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
  <!-- endinject -->
</body>

</html>
