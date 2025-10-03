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

// Buscar notas do aluno
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

// Calcular médias por disciplina
$medias_disciplinas = [];
foreach ($notas as $nota) {
    $disciplina = $nota['disciplina_nome'];
    if (!isset($medias_disciplinas[$disciplina])) {
        $medias_disciplinas[$disciplina] = [
            'medias' => [],
            'turma' => $nota['turma_nome']
        ];
    }
    
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
    
    $media = $count_notas > 0 ? $soma_notas / $count_notas : 0;
    $medias_disciplinas[$disciplina]['medias'][] = $media;
}

// Calcular média geral
$media_geral = 0;
$total_disciplinas = count($medias_disciplinas);
if ($total_disciplinas > 0) {
    $soma_medias = 0;
    foreach ($medias_disciplinas as $disciplina => $dados) {
        if (!empty($dados['medias'])) {
            $soma_medias += array_sum($dados['medias']) / count($dados['medias']);
        }
    }
    $media_geral = $soma_medias / $total_disciplinas;
}

// Buscar pareceres do aluno
$pareceres = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome as professor_nome 
        FROM pareceres p 
        JOIN usuarios u ON p.id_professor_designado = u.id 
        WHERE p.id_aluno = ? 
        ORDER BY p.periodo DESC, p.unidade DESC
    ");
    $stmt->execute([$aluno_id]);
    $pareceres = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar pareceres: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard do Aluno - <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../assets/vendors/chart.js/Chart.min.css">
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
    <!-- partial:../partials/_navbar.html -->
    <?php include 'partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../partials/_sidebar.html -->
      <?php include 'partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Dashboard do Aluno </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Página Inicial</li>
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
                      <h4 class="card-title">Bem-vindo(a), <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?>!</h4>
                      <p class="card-description">Acompanhe seu desempenho escolar e acesse seus documentos.</p>
                    </div>
                    <div class="col-md-4 text-right">
                      <div class="badge badge-info p-2">
                        <i class="mdi mdi-school"></i> <?= htmlspecialchars($aluno['turma_nome'] ?? 'Turma não definida') ?>
                      </div>
                      <div class="badge badge-success p-2 ml-2">
                        <i class="mdi mdi-calendar"></i> <?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Estatísticas -->
          <div class="row">
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= number_format($media_geral, 1) ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-chart-line icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Média Geral</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= count($medias_disciplinas) ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
                        <span class="mdi mdi-book-open-page-variant icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Disciplinas</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= count($pareceres) ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-warning">
                        <span class="mdi mdi-file-document icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Pareceres</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $media_geral >= 7 ? 'Aprovado' : 'Atenção' ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-<?= $media_geral >= 7 ? 'success' : 'danger' ?>">
                        <span class="mdi mdi-<?= $media_geral >= 7 ? 'check-circle' : 'alert' ?> icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Status</h6>
                </div>
              </div>
            </div>
          </div>

          <!-- Notas por Disciplina -->
          <div class="row">
            <div class="col-lg-8 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Notas por Disciplina</h4>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Disciplina</th>
                          <th>1ª Unidade</th>
                          <th>2ª Unidade</th>
                          <th>3ª Unidade</th>
                          <th>4ª Unidade</th>
                          <th>Média</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($medias_disciplinas as $disciplina => $dados): ?>
                          <tr>
                            <td><strong><?= htmlspecialchars($disciplina) ?></strong></td>
                            <?php 
                            $medias = $dados['medias'];
                            $media_disciplina = array_sum($medias) / count($medias);
                            ?>
                            <td><?= number_format($medias[0] ?? 0, 1) ?></td>
                            <td><?= number_format($medias[1] ?? 0, 1) ?></td>
                            <td><?= number_format($medias[2] ?? 0, 1) ?></td>
                            <td><?= number_format($medias[3] ?? 0, 1) ?></td>
                            <td>
                              <span class="badge badge-<?= $media_disciplina >= 7 ? 'success' : 'warning' ?>">
                                <?= number_format($media_disciplina, 1) ?>
                              </span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pareceres Recentes -->
            <div class="col-lg-4 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Pareceres Recentes</h4>
                  <?php if (empty($pareceres)): ?>
                    <p class="text-muted">Nenhum parecer disponível.</p>
                  <?php else: ?>
                    <?php foreach (array_slice($pareceres, 0, 5) as $parecer): ?>
                      <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between">
                          <h6 class="mb-1"><?= htmlspecialchars($parecer['professor_nome']) ?></h6>
                          <small class="text-muted"><?= $parecer['unidade'] ?>ª Unidade</small>
                        </div>
                        <p class="text-muted mb-1"><?= htmlspecialchars($parecer['periodo']) ?></p>
                        <div class="badge badge-info"><?= htmlspecialchars($parecer['status']) ?></div>
                      </div>
                    <?php endforeach; ?>
                    <?php if (count($pareceres) > 5): ?>
                      <a href="pareceres.php" class="btn btn-outline-primary btn-sm">Ver todos os pareceres</a>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Ações Rápidas -->
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Ações Rápidas</h4>
                  <div class="row">
                    <div class="col-md-3">
                      <a href="notas.php" class="btn btn-outline-primary btn-block">
                        <i class="mdi mdi-chart-line"></i><br>
                        Ver Notas Detalhadas
                      </a>
                    </div>
                    <div class="col-md-3">
                      <a href="pareceres.php" class="btn btn-outline-info btn-block">
                        <i class="mdi mdi-file-document"></i><br>
                        Pareceres Pedagógicos
                      </a>
                    </div>
                    <div class="col-md-3">
                      <a href="declaracoes.php" class="btn btn-outline-success btn-block">
                        <i class="mdi mdi-certificate"></i><br>
                        Gerar Declarações
                      </a>
                    </div>
                    <div class="col-md-3">
                      <a href="perfil.php" class="btn btn-outline-warning btn-block">
                        <i class="mdi mdi-account"></i><br>
                        Meu Perfil
                      </a>
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
  <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="../assets/vendors/chart.js/Chart.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <script src="../assets/js/settings.js"></script>
  <script src="../assets/js/todolist.js"></script>
  <script src="../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../assets/js/dashboard.js"></script>
  <!-- End custom js for this page -->
</body>

</html>
