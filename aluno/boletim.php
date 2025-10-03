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
        die("Aluno não encontrado.");
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do aluno: " . $e->getMessage());
    die("Erro interno do sistema.");
}

// Buscar notas do aluno
try {
    $stmt = $pdo->prepare("
        SELECT n.*, d.nome as disciplina_nome, d.carga_horaria,
               u.nome as professor_nome
        FROM notas n
        LEFT JOIN disciplinas d ON n.disciplina_id = d.id
        LEFT JOIN usuarios u ON n.professor_id = u.id
        WHERE n.aluno_id = ?
        ORDER BY d.nome, n.unidade
    ");
    $stmt->execute([$aluno_id]);
    $notas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar notas: " . $e->getMessage());
    $notas = [];
}

// Organizar notas por disciplina
$disciplinas_notas = [];
foreach ($notas as $nota) {
    $disciplina = $nota['disciplina_nome'];
    if (!isset($disciplinas_notas[$disciplina])) {
        $disciplinas_notas[$disciplina] = [
            'carga_horaria' => $nota['carga_horaria'],
            'professor' => $nota['professor_nome'],
            'notas' => []
        ];
    }
    
    // Criar estrutura de notas por unidade baseada na estrutura real da tabela
    if (!isset($disciplinas_notas[$disciplina]['notas'][1]) && $nota['media_1'] !== null) {
        $disciplinas_notas[$disciplina]['notas'][1] = [
            'nota' => $nota['media_1'],
            'unidade' => 1
        ];
    }
    if (!isset($disciplinas_notas[$disciplina]['notas'][2]) && $nota['media_2'] !== null) {
        $disciplinas_notas[$disciplina]['notas'][2] = [
            'nota' => $nota['media_2'],
            'unidade' => 2
        ];
    }
    if (!isset($disciplinas_notas[$disciplina]['notas'][3]) && $nota['media_3'] !== null) {
        $disciplinas_notas[$disciplina]['notas'][3] = [
            'nota' => $nota['media_3'],
            'unidade' => 3
        ];
    }
    if (!isset($disciplinas_notas[$disciplina]['notas'][4]) && $nota['media_4'] !== null) {
        $disciplinas_notas[$disciplina]['notas'][4] = [
            'nota' => $nota['media_4'],
            'unidade' => 4
        ];
    }
}

// Calcular médias
$medias_gerais = [];
foreach ($disciplinas_notas as $disciplina => $dados) {
    $soma_notas = 0;
    $count_notas = 0;
    
    // Calcular média baseada na estrutura real da tabela
    foreach ($dados['notas'] as $unidade => $nota) {
        if ($nota['nota'] !== null && $nota['nota'] > 0) {
            $soma_notas += $nota['nota'];
            $count_notas++;
        }
    }
    
    if ($count_notas > 0) {
        $medias_gerais[$disciplina] = round($soma_notas / $count_notas, 1);
    } else {
        $medias_gerais[$disciplina] = 0;
    }
}

// Calcular média geral
$media_geral = 0;
if (!empty($medias_gerais)) {
    $media_geral = round(array_sum($medias_gerais) / count($medias_gerais), 1);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Boletim Escolar - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>"
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <!-- Chart.js não requer CSS separado -->
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
    <!-- partial:partials/_navbar.php -->
    <?php include('partials/_navbar.php'); ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.php -->
      <?php include('partials/_sidebar.php'); ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <!-- Cabeçalho do Boletim -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-8">
                      <h4 class="card-title">
                        <i class="mdi mdi-school"></i> Boletim Escolar
                      </h4>
                      <h6 class="text-muted">
                        <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?> - 
                        <?= htmlspecialchars($aluno['turma_nome']) ?> - 
                        Ano Letivo: <?= htmlspecialchars($aluno['ano_letivo']) ?>
                      </h6>
                    </div>
                    <div class="col-md-4 text-right">
                      <button type="button" class="btn btn-gradient-primary" onclick="gerarPDF()">
                        <i class="mdi mdi-file-pdf"></i> Gerar PDF
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Resumo Geral -->
          <div class="row">
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Média Geral</h6>
                      <h3 class="text-primary"><?= $media_geral ?></h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-chart-line text-primary" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Disciplinas</h6>
                      <h3 class="text-success"><?= count($disciplinas_notas) ?></h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-book-open-page-variant text-success" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Status</h6>
                      <h3 class="<?= $media_geral >= 6 ? 'text-success' : 'text-danger' ?>">
                        <?= $media_geral >= 6 ? 'Aprovado' : 'Reprovado' ?>
                      </h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-<?= $media_geral >= 6 ? 'check-circle' : 'close-circle' ?> <?= $media_geral >= 6 ? 'text-success' : 'text-danger' ?>" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Gráfico de Desempenho -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Desempenho por Disciplina</h5>
                  <canvas id="graficoDesempenho" width="400" height="200"></canvas>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tabela de Notas -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Notas por Unidade</h5>
                  
                  <?php if (empty($disciplinas_notas)): ?>
                    <div class="text-center py-5">
                      <i class="mdi mdi-school-outline" style="font-size: 64px; color: #ccc;"></i>
                      <h5 class="mt-3 text-muted">Nenhuma nota encontrada</h5>
                      <p class="text-muted">Suas notas aparecerão aqui quando forem lançadas pelos professores.</p>
                    </div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead class="thead-light">
                          <tr>
                            <th>Disciplina</th>
                            <th>Professor</th>
                            <th>1ª Unidade</th>
                            <th>2ª Unidade</th>
                            <th>3ª Unidade</th>
                            <th>4ª Unidade</th>
                            <th>Média</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($disciplinas_notas as $disciplina => $dados): ?>
                            <tr>
                              <td><strong><?= htmlspecialchars($disciplina) ?></strong></td>
                              <td><?= htmlspecialchars($dados['professor']) ?></td>
                              <td class="text-center">
                                <?php if (isset($dados['notas'][1])): ?>
                                  <span class="badge badge-<?= $dados['notas'][1]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                    <?= $dados['notas'][1]['nota'] ?>
                                  </span>
                                <?php else: ?>
                                  <span class="text-muted">-</span>
                                <?php endif; ?>
                              </td>
                              <td class="text-center">
                                <?php if (isset($dados['notas'][2])): ?>
                                  <span class="badge badge-<?= $dados['notas'][2]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                    <?= $dados['notas'][2]['nota'] ?>
                                  </span>
                                <?php else: ?>
                                  <span class="text-muted">-</span>
                                <?php endif; ?>
                              </td>
                              <td class="text-center">
                                <?php if (isset($dados['notas'][3])): ?>
                                  <span class="badge badge-<?= $dados['notas'][3]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                    <?= $dados['notas'][3]['nota'] ?>
                                  </span>
                                <?php else: ?>
                                  <span class="text-muted">-</span>
                                <?php endif; ?>
                              </td>
                              <td class="text-center">
                                <?php if (isset($dados['notas'][4])): ?>
                                  <span class="badge badge-<?= $dados['notas'][4]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                    <?= $dados['notas'][4]['nota'] ?>
                                  </span>
                                <?php else: ?>
                                  <span class="text-muted">-</span>
                                <?php endif; ?>
                              </td>
                              <td class="text-center">
                                <strong class="text-<?= $medias_gerais[$disciplina] >= 6 ? 'success' : 'danger' ?>">
                                  <?= $medias_gerais[$disciplina] ?>
                                </strong>
                              </td>
                              <td class="text-center">
                                <?php if ($medias_gerais[$disciplina] >= 6): ?>
                                  <span class="badge badge-success">Aprovado</span>
                                <?php else: ?>
                                  <span class="badge badge-danger">Reprovado</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-dark">
                          <tr>
                            <td colspan="6"><strong>MÉDIA GERAL</strong></td>
                            <td class="text-center">
                              <strong class="text-white"><?= $media_geral ?></strong>
                            </td>
                            <td class="text-center">
                              <span class="badge badge-<?= $media_geral >= 6 ? 'success' : 'danger' ?>">
                                <?= $media_geral >= 6 ? 'APROVADO' : 'REPROVADO' ?>
                              </span>
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.php -->
        <?php include __DIR__ . '/../secretaria/partials/_footer.php'; ?>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- plugins:js -->
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
  <!-- endinject -->
  
  <script>
    // Dados para o gráfico
    var disciplinas = <?= json_encode(array_keys($disciplinas_notas)) ?>;
    var medias = <?= json_encode(array_values($medias_gerais)) ?>;
    
    // Aguardar o Chart.js carregar
    document.addEventListener('DOMContentLoaded', function() {
      // Verificar se Chart está disponível
      if (typeof Chart === 'undefined') {
        console.error('Chart.js não foi carregado corretamente');
        return;
      }
      
      // Criar gráfico
      var ctx = document.getElementById('graficoDesempenho').getContext('2d');
      var grafico = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: disciplinas,
        datasets: [{
          label: 'Média por Disciplina',
          data: medias,
          backgroundColor: medias.map(function(media) {
            return media >= 6 ? 'rgba(40, 167, 69, 0.8)' : 'rgba(220, 53, 69, 0.8)';
          }),
          borderColor: medias.map(function(media) {
            return media >= 6 ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)';
          }),
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 10,
            ticks: {
              stepSize: 1
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
    });
    
    function gerarPDF() {
      window.open('gerar_pdf_boletim.php', '_blank');
    }
  </script>
</body>

</html>
