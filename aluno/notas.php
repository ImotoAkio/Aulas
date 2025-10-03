<?php
session_start();
include('../secretaria/partials/db.php');

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

// Buscar todas as notas do aluno
$notas = [];
try {
    $stmt = $pdo->prepare("
        SELECT n.*, d.nome as disciplina_nome, t.nome as turma_nome
        FROM notas n 
        JOIN disciplinas d ON n.disciplina_id = d.id 
        JOIN turmas t ON n.turma_id = t.id 
        WHERE n.aluno_id = ? 
        ORDER BY d.nome, n.unidade
    ");
    $stmt->execute([$aluno_id]);
    $notas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar notas: " . $e->getMessage());
}

// Organizar notas por disciplina
$notas_por_disciplina = [];
foreach ($notas as $nota) {
    $disciplina = $nota['disciplina_nome'];
    if (!isset($notas_por_disciplina[$disciplina])) {
        $notas_por_disciplina[$disciplina] = [];
    }
    $notas_por_disciplina[$disciplina][] = $nota;
}

// Calcular médias finais
$medias_finais = [];
foreach ($notas_por_disciplina as $disciplina => $notas_disciplina) {
    $soma_medias = 0;
    $count_medias = 0;
    
    foreach ($notas_disciplina as $nota) {
        // Calcular média baseada na estrutura correta da tabela
        $soma_notas = 0;
        $count_notas = 0;
        
        // Verificar se existem campos de média individuais
        if (isset($nota['media_1']) && $nota['media_1'] !== null) {
            $soma_notas += $nota['media_1'];
            $count_notas++;
        }
        if (isset($nota['media_2']) && $nota['media_2'] !== null) {
            $soma_notas += $nota['media_2'];
            $count_notas++;
        }
        if (isset($nota['media_3']) && $nota['media_3'] !== null) {
            $soma_notas += $nota['media_3'];
            $count_notas++;
        }
        if (isset($nota['media_4']) && $nota['media_4'] !== null) {
            $soma_notas += $nota['media_4'];
            $count_notas++;
        }
        
        // Se não há campos de média individuais, usar o campo 'nota'
        if ($count_notas === 0 && isset($nota['nota']) && $nota['nota'] !== null) {
            $soma_notas = $nota['nota'];
            $count_notas = 1;
        }
        
        $media_unidade = $count_notas > 0 ? $soma_notas / $count_notas : 0;
        $soma_medias += $media_unidade;
        $count_medias++;
    }
    
    $medias_finais[$disciplina] = $count_medias > 0 ? $soma_medias / $count_medias : 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Minhas Notas - <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../assets/vendors/datatables/dataTables.bootstrap4.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../assets/images/favicon.png" />
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
            <h3 class="page-title"> Minhas Notas </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Minhas Notas</li>
              </ol>
            </nav>
          </div>

          <!-- Informações do Aluno -->
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-8">
                      <h4 class="card-title"><?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></h4>
                      <p class="card-description">
                        <strong>Turma:</strong> <?= htmlspecialchars($aluno['turma_nome'] ?? 'Não definida') ?> | 
                        <strong>Ano Letivo:</strong> <?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?>
                      </p>
                    </div>
                    <div class="col-md-4 text-right">
                      <a href="boletim.php" class="btn btn-primary">
                        <i class="mdi mdi-file-pdf"></i> Gerar Boletim PDF
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Resumo das Médias -->
          <div class="row">
            <?php foreach ($medias_finais as $disciplina => $media): ?>
              <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-9">
                        <div class="d-flex align-items-center align-self-start">
                          <h3 class="mb-0"><?= number_format($media, 1) ?></h3>
                        </div>
                      </div>
                      <div class="col-3">
                        <div class="icon icon-box-<?= $media >= 7 ? 'success' : 'warning' ?>">
                          <span class="mdi mdi-book-open-page-variant icon-item"></span>
                        </div>
                      </div>
                    </div>
                    <h6 class="text-muted font-weight-normal"><?= htmlspecialchars($disciplina) ?></h6>
                    <div class="progress mt-3">
                      <div class="progress-bar bg-<?= $media >= 7 ? 'success' : 'warning' ?>" 
                           role="progressbar" style="width: <?= min(100, ($media / 10) * 100) ?>%"></div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Tabela Detalhada de Notas -->
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Notas Detalhadas por Disciplina</h4>
                  
                  <?php if (empty($notas_por_disciplina)): ?>
                    <div class="alert alert-info">
                      <i class="mdi mdi-information"></i> Nenhuma nota foi lançada ainda.
                    </div>
                  <?php else: ?>
                    <?php foreach ($notas_por_disciplina as $disciplina => $notas_disciplina): ?>
                      <div class="mb-4">
                        <h5 class="text-primary">
                          <i class="mdi mdi-book-open"></i> <?= htmlspecialchars($disciplina) ?>
                        </h5>
                        <div class="table-responsive">
                          <table class="table table-striped">
                            <thead>
                              <tr>
                                <th>Unidade</th>
                                <th>1ª Avaliação</th>
                                <th>2ª Avaliação</th>
                                <th>Média da Unidade</th>
                                <th>Status</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($notas_disciplina as $nota): ?>
                                <tr>
                                  <td><strong><?= $nota['unidade'] ?>ª Unidade</strong></td>
                                  <td>
                                    <span class="badge badge-<?= $nota['media_1'] >= 7 ? 'success' : 'warning' ?>">
                                      <?= number_format($nota['media_1'], 1) ?>
                                    </span>
                                  </td>
                                  <td>
                                    <span class="badge badge-<?= $nota['media_2'] >= 7 ? 'success' : 'warning' ?>">
                                      <?= number_format($nota['media_2'], 1) ?>
                                    </span>
                                  </td>
                                  <td>
                                    <?php 
                                    // Calcular média baseada na estrutura correta da tabela
                                    $soma_notas = 0;
                                    $count_notas = 0;
                                    
                                    if (isset($nota['media_1']) && $nota['media_1'] !== null) {
                                        $soma_notas += $nota['media_1'];
                                        $count_notas++;
                                    }
                                    if (isset($nota['media_2']) && $nota['media_2'] !== null) {
                                        $soma_notas += $nota['media_2'];
                                        $count_notas++;
                                    }
                                    if (isset($nota['media_3']) && $nota['media_3'] !== null) {
                                        $soma_notas += $nota['media_3'];
                                        $count_notas++;
                                    }
                                    if (isset($nota['media_4']) && $nota['media_4'] !== null) {
                                        $soma_notas += $nota['media_4'];
                                        $count_notas++;
                                    }
                                    
                                    if ($count_notas === 0 && isset($nota['nota']) && $nota['nota'] !== null) {
                                        $soma_notas = $nota['nota'];
                                        $count_notas = 1;
                                    }
                                    
                                    $media_unidade = $count_notas > 0 ? $soma_notas / $count_notas : 0;
                                    ?>
                                    <span class="badge badge-<?= $media_unidade >= 7 ? 'success' : 'warning' ?>">
                                      <?= number_format($media_unidade, 1) ?>
                                    </span>
                                  </td>
                                  <td>
                                    <?php if ($media_unidade >= 7): ?>
                                      <span class="badge badge-success">Aprovado</span>
                                    <?php else: ?>
                                      <span class="badge badge-warning">Atenção</span>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Gráfico de Desempenho -->
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Gráfico de Desempenho</h4>
                  <canvas id="desempenhoChart" width="400" height="200"></canvas>
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
  <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="../assets/vendors/chart.js/Chart.min.js"></script>
  <script src="../assets/vendors/datatables/jquery.dataTables.js"></script>
  <script src="../assets/vendors/datatables/dataTables.bootstrap4.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <script src="../assets/js/settings.js"></script>
  <script src="../assets/js/todolist.js"></script>
  <script src="../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../assets/js/data-table.js"></script>
  <!-- End custom js for this page -->

  <script>
    // Gráfico de desempenho
    var ctx = document.getElementById('desempenhoChart').getContext('2d');
    var desempenhoChart = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: [<?= implode(',', array_map(function($d) { return '"' . addslashes($d) . '"'; }, array_keys($medias_finais))) ?>],
        datasets: [{
          label: 'Médias por Disciplina',
          data: [<?= implode(',', array_values($medias_finais)) ?>],
          backgroundColor: 'rgba(102, 126, 234, 0.2)',
          borderColor: 'rgba(102, 126, 234, 1)',
          borderWidth: 2,
          pointBackgroundColor: 'rgba(102, 126, 234, 1)',
          pointBorderColor: '#fff',
          pointHoverBackgroundColor: '#fff',
          pointHoverBorderColor: 'rgba(102, 126, 234, 1)'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            beginAtZero: true,
            max: 10,
            ticks: {
              stepSize: 2
            }
          }
        },
        plugins: {
          legend: {
            display: true,
            position: 'top'
          }
        }
      }
    });
  </script>
</body>

</html>
