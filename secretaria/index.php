<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

include 'partials/db.php';

try {
  // Query SQL para contar planos de aula pendentes
  $sql = "SELECT COUNT(*) AS total_pendente FROM planos_aula WHERE status = 'pendente'";

  // Prepara a query
  $stmt = $pdo->prepare($sql);

  // Executa a query
  $stmt->execute();

  // Obtém o resultado
  $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

  // Exibe a quantidade de planos de aula pendentes
  if ($resultado) {
    $total_pendente = $resultado['total_pendente'];
    // echo "Quantidade de planos de aula pendentes: " . $total_pendente; // Comentado para não exibir diretamente
  } else {
    // echo "Não foi possível obter a contagem de planos de aula."; // Comentado para não exibir diretamente
    $total_pendente = 0; // Definir como 0 se não houver resultado
  }

} catch (PDOException $e) {
  // Em caso de erro na conexão ou na query
  echo "Erro: " . $e->getMessage();
  $total_pendente = 0; // Definir como 0 em caso de erro
}

// Novas Queries para o Dashboard
try {
    // 1. Total de Alunos Ativos
    $stmt = $pdo->query("SELECT COUNT(*) FROM alunos WHERE status_cadastro = 'completo' OR status_cadastro = 'aprovado'");
    $total_alunos = $stmt->fetchColumn();

    // 2. Total de Turmas
    $stmt = $pdo->query("SELECT COUNT(*) FROM turmas");
    $total_turmas = $stmt->fetchColumn();

    // 3. Pré-cadastros Pendentes (já temos a query de planos, vamos adicionar esta)
    $stmt = $pdo->query("SELECT COUNT(*) FROM pre_cadastros_controle WHERE status = 'pendente'");
    $total_pre_cadastros_pendentes = $stmt->fetchColumn();

    // 4. Dados para o Gráfico de Alunos por Turma (Top 10)
    $stmt = $pdo->query("
        SELECT t.nome, COUNT(a.id) as qtd 
        FROM turmas t 
        LEFT JOIN alunos a ON t.id = a.turma_id 
        GROUP BY t.id 
        ORDER BY qtd DESC 
        LIMIT 10
    ");
    $dados_turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labels_turmas = json_encode(array_column($dados_turmas, 'nome'));
    $values_turmas = json_encode(array_column($dados_turmas, 'qtd'));

    // 5. Dados para o Gráfico de Status dos Pré-cadastros
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as qtd 
        FROM pre_cadastros_controle 
        GROUP BY status
    ");
    $dados_pre = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // 'pendente' => 10, 'aprovado' => 20
    
    // Garantir que todas as chaves existam para o gráfico
    $status_labels = ['pendente', 'aprovado', 'cancelado'];
    $status_values = [];
    foreach ($status_labels as $s) {
        $status_values[] = $dados_pre[$s] ?? 0;
    }
    $labels_pre = json_encode(array_map('ucfirst', $status_labels));
    $values_pre = json_encode($status_values);

} catch (PDOException $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    // Definir valores padrão em caso de erro para evitar quebrar a página
    $total_alunos = 0;
    $total_turmas = 0;
    $total_pre_cadastros_pendentes = 0;
    $labels_turmas = json_encode([]);
    $values_turmas = json_encode([]);
    $labels_pre = json_encode(['Pendente', 'Aprovado', 'Cancelado']);
    $values_pre = json_encode([0, 0, 0]);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Rosa de Sharom | Echo Edu</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css"); ?>">
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
    <?php include 'partials/_navbar.php'; ?> <!-- Barra de navegação-->

    <?php include 'partials/_sidebar.php'; ?> <!-- Barra lateral-->

    <!--pagina-->
    <div class="main-panel">
      <div class="content-wrapper">
        <div class="page-header">
          <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
              <i class="mdi mdi-home"></i>
            </span> Início
          </h3>
          <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
              <li class="breadcrumb-item active" aria-current="page">
                <span></span>Visão Geral <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
              </li>
            </ul>
          </nav>
        </div>

        <!-- Cards de Resumo -->
        <div class="row">
          <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-primary card-img-holder text-white">
              <div class="card-body">
                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Total de Alunos <i class="mdi mdi-account-multiple mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5"><?php echo $total_alunos ?? 0; ?></h2>
                <h6 class="card-text">Matriculados</h6>
              </div>
            </div>
          </div>
          <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
              <div class="card-body">
                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Total de Turmas <i class="mdi mdi-school mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5"><?php echo $total_turmas ?? 0; ?></h2>
                <h6 class="card-text">Ativas</h6>
              </div>
            </div>
          </div>
          <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-warning card-img-holder text-white">
              <div class="card-body">
                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Pré-cadastros <i class="mdi mdi-account-clock mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5"><?php echo $total_pre_cadastros_pendentes ?? 0; ?></h2>
                <h6 class="card-text">Pendentes de Aprovação</h6>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-7 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="clearfix">
                            <h4 class="card-title float-start">Alunos por Turma</h4>
                            <div id="visit-sale-chart-legend" class="rounded-legend legend-horizontal legend-top-right float-end"></div>
                        </div>
                        <canvas id="alunosTurmaChart" class="mt-4"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-5 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Status Pré-cadastros</h4>
                        <canvas id="preCadastroChart"></canvas>
                        <div id="traffic-chart-legend" class="rounded-legend legend-vertical legend-bottom-left pt-4"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
          <!--planos de aulas-->
          <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='planos.php'"
            style="cursor: pointer;">
            <div class="card bg-gradient-danger card-img-holder text-white">
              <div class="card-body">
                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Planos de Aula <i class="mdi mdi-chart-line mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5"> <?php echo $total_pendente; ?> Planos para revisão</h2>
                <!--exibe a quantidade de planos pendentes-->
                <h6 class="card-text">Revisar agora</h6>
              </div>
            </div>
          </div>
          <!--notas-->
          <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='notas.php'"
            style="cursor: pointer;">
            <div class="card bg-gradient-info card-img-holder text-white">
              <div class="card-body">
                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Notas<i class="mdi mdi-bookmark-outline mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5">Visualizar Boletim Escolar</h2>

              </div>
            </div>
          </div>
          <!--parecer-->
          <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='parecer.php'"
            style="cursor: pointer;">
            <div class="card bg-gradient-success card-img-holder text-white">
              <div class="card-body">
                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Parecer Pedagógico <i
                    class="mdi mdi-diamond mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5">Visualizar Resultados</h2>

              </div>
            </div>
          </div>
        </div>


      </div>
      <!-- content-wrapper ends -->
      <!-- partial:partials/_footer.html -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2023 <a
              href="https://www.bootstrapdash.com/" target="_blank">BootstrapDash</a>. All rights reserved.</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
            <i class="mdi mdi-server"></i> PHP: <?php echo phpversion(); ?> | 
            <i class="mdi mdi-database"></i> DB: Online | 
            <i class="mdi mdi-clock"></i> <?php echo date('H:i'); ?>
          </span>
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
  <script src="<?php echo getAssetUrl("assets/vendors/chart.js/chart.umd.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"); ?>"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
      <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
            <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
        <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
        <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
        <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="<?php echo getAssetUrl("assets/js/dashboard.js"); ?>"></script>
  
  <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Gráfico de Alunos por Turma (Bar Chart)
        if ($("#alunosTurmaChart").length) {
            var ctx = document.getElementById('alunosTurmaChart').getContext("2d");
            var alunosTurmaChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo $labels_turmas; ?>,
                    datasets: [{
                        label: 'Qtd. Alunos',
                        data: <?php echo $values_turmas; ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // Gráfico de Pré-cadastros (Doughnut Chart)
        if ($("#preCadastroChart").length) {
            var ctx = document.getElementById('preCadastroChart').getContext("2d");
            var preCadastroChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $labels_pre; ?>,
                    datasets: [{
                        data: <?php echo $values_pre; ?>,
                        backgroundColor: [
                            'rgba(255, 206, 86, 0.5)', // Pendente (Amarelo)
                            'rgba(75, 192, 192, 0.5)', // Aprovado (Verde)
                            'rgba(255, 99, 132, 0.5)'  // Cancelado (Vermelho)
                        ],
                        borderColor: [
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    });
  </script>
  <!-- End custom js for this page -->
</body>

</html>