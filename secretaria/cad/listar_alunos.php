<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: ../../login.php');
    exit();
}

// Buscar alunos com informações de turma
$alunos = [];
try {
    $stmt = $pdo->query("
        SELECT a.*, t.nome as turma_nome, t.ano_letivo 
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        ORDER BY a.nome_completo, a.nome
    ");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar alunos: " . $e->getMessage());
}

// Contar alunos com cadastro completo vs incompleto
$total_alunos = count($alunos);
$alunos_completos = 0;
$alunos_incompletos = 0;

foreach ($alunos as $aluno) {
    if (!empty($aluno['nome_completo']) && !empty($aluno['data_nascimento']) && !empty($aluno['cpf'])) {
        $alunos_completos++;
    } else {
        $alunos_incompletos++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Listar Alunos</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../../assets/vendors/datatables/dataTables.bootstrap4.css">
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
            <h3 class="page-title"> Listar Alunos </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Listar Alunos</li>
              </ol>
            </nav>
          </div>

          <!-- Estatísticas -->
          <div class="row">
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
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-account-multiple icon-item"></span>
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
                        <h3 class="mb-0"><?= $alunos_completos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-check-circle icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Cadastros Completos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $alunos_incompletos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-warning">
                        <span class="mdi mdi-alert icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Cadastros Incompletos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $total_alunos > 0 ? round(($alunos_completos / $total_alunos) * 100, 1) : 0 ?>%</h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
                        <span class="mdi mdi-percent icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Taxa de Completude</h6>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabela de Alunos -->
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Lista de Alunos</h4>
                  <p class="card-description">Gerencie os cadastros dos alunos. Clique em "Editar" para completar informações faltantes.</p>
                  
                  <div class="table-responsive">
                    <table class="table table-striped" id="tabelaAlunos">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Nome</th>
                          <th>Turma</th>
                          <th>Ano Letivo</th>
                          <th>CPF</th>
                          <th>Data Nasc.</th>
                          <th>Status</th>
                          <th>Ações</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                          <tr>
                            <td><?= $aluno['id'] ?></td>
                            <td>
                              <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?>
                              <?php if (empty($aluno['nome_completo'])): ?>
                                <span class="badge badge-warning">Nome antigo</span>
                              <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($aluno['turma_nome'] ?? 'Sem turma') ?></td>
                            <td><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></td>
                            <td>
                              <?php if (!empty($aluno['cpf'])): ?>
                                <?= htmlspecialchars($aluno['cpf']) ?>
                              <?php else: ?>
                                <span class="text-muted">Não informado</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if (!empty($aluno['data_nascimento'])): ?>
                                <?= date('d/m/Y', strtotime($aluno['data_nascimento'])) ?>
                              <?php else: ?>
                                <span class="text-muted">Não informado</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if (!empty($aluno['nome_completo']) && !empty($aluno['data_nascimento']) && !empty($aluno['cpf'])): ?>
                                <span class="badge badge-success">Completo</span>
                              <?php else: ?>
                                <span class="badge badge-warning">Incompleto</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <a href="editar_aluno.php?id=<?= $aluno['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-pencil"></i> Editar
                              </a>
                              <a href="visualizar_aluno.php?id=<?= $aluno['id'] ?>" class="btn btn-info btn-sm">
                                <i class="mdi mdi-eye"></i> Visualizar
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
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
  <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="../../assets/vendors/datatables/jquery.dataTables.js"></script>
  <script src="../../assets/vendors/datatables/dataTables.bootstrap4.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../../assets/js/off-canvas.js"></script>
  <script src="../../assets/js/misc.js"></script>
  <script src="../../assets/js/settings.js"></script>
  <script src="../../assets/js/todolist.js"></script>
  <script src="../../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../../assets/js/data-table.js"></script>
  <!-- End custom js for this page -->

  <script>
    $(document).ready(function() {
      $('#tabelaAlunos').DataTable({
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
        },
        "pageLength": 25,
        "order": [[1, "asc"]]
      });
    });
  </script>
</body>

</html>
