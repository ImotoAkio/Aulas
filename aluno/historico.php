<?php
session_start();
include('../secretaria/partials/db.php');

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    header('Location: ../../login.php');
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

// Buscar histórico completo do aluno
try {
    $stmt = $pdo->prepare("
        SELECT n.*, d.nome as disciplina_nome, d.carga_horaria,
               u.nome as professor_nome, t.nome as turma_nome, t.ano_letivo
        FROM notas n
        LEFT JOIN disciplinas d ON n.disciplina_id = d.id
        LEFT JOIN usuarios u ON n.professor_id = u.id
        LEFT JOIN turmas t ON n.turma_id = t.id
        WHERE n.aluno_id = ?
        ORDER BY t.ano_letivo DESC, d.nome, n.unidade
    ");
    $stmt->execute([$aluno_id]);
    $historico = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar histórico: " . $e->getMessage());
    $historico = [];
}

// Organizar histórico por ano letivo
$historico_por_ano = [];
foreach ($historico as $nota) {
    $ano = $nota['ano_letivo'];
    if (!isset($historico_por_ano[$ano])) {
        $historico_por_ano[$ano] = [
            'turma' => $nota['turma_nome'],
            'disciplinas' => []
        ];
    }
    
    $disciplina = $nota['disciplina_nome'];
    if (!isset($historico_por_ano[$ano]['disciplinas'][$disciplina])) {
        $historico_por_ano[$ano]['disciplinas'][$disciplina] = [
            'professor' => $nota['professor_nome'],
            'carga_horaria' => $nota['carga_horaria'],
            'notas' => []
        ];
    }
    
    $historico_por_ano[$ano]['disciplinas'][$disciplina]['notas'][$nota['unidade']] = $nota;
}

// Calcular médias por ano
$medias_por_ano = [];
foreach ($historico_por_ano as $ano => $dados) {
    $medias_por_ano[$ano] = [];
    
    foreach ($dados['disciplinas'] as $disciplina => $dados_disc) {
        $soma_notas = 0;
        $count_notas = 0;
        
        foreach ($dados_disc['notas'] as $unidade => $nota) {
            if ($nota['nota'] !== null) {
                $soma_notas += $nota['nota'];
                $count_notas++;
            }
        }
        
        if ($count_notas > 0) {
            $medias_por_ano[$ano][$disciplina] = round($soma_notas / $count_notas, 1);
        } else {
            $medias_por_ano[$ano][$disciplina] = 0;
        }
    }
}

// Calcular média geral por ano
$media_geral_por_ano = [];
foreach ($medias_por_ano as $ano => $medias) {
    if (!empty($medias)) {
        $media_geral_por_ano[$ano] = round(array_sum($medias) / count($medias), 1);
    } else {
        $media_geral_por_ano[$ano] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Histórico Escolar - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../assets/images/favicon.png" />
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
          <!-- Cabeçalho do Histórico -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-8">
                      <h4 class="card-title">
                        <i class="mdi mdi-history"></i> Histórico Escolar
                      </h4>
                      <h6 class="text-muted">
                        <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?> - 
                        CPF: <?= htmlspecialchars($aluno['cpf']) ?>
                      </h6>
                    </div>
                    <div class="col-md-4 text-right">
                      <button type="button" class="btn btn-gradient-primary" onclick="gerarPDF()">
                        <i class="mdi mdi-file-pdf"></i> Gerar PDF
                      </button>
                      <button type="button" class="btn btn-outline-info" onclick="imprimirHistorico()">
                        <i class="mdi mdi-printer"></i> Imprimir
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Resumo Geral -->
          <div class="row">
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Anos Cursados</h6>
                      <h3 class="text-info"><?= count($historico_por_ano) ?></h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-calendar text-info" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Disciplinas</h6>
                      <h3 class="text-success"><?= count(array_unique(array_column($historico, 'disciplina_nome'))) ?></h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-book-open-page-variant text-success" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Média Geral</h6>
                      <h3 class="text-primary">
                        <?php 
                        $media_geral_total = 0;
                        if (!empty($media_geral_por_ano)) {
                            $media_geral_total = round(array_sum($media_geral_por_ano) / count($media_geral_por_ano), 1);
                        }
                        echo $media_geral_total;
                        ?>
                      </h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-chart-line text-primary" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <h6 class="text-muted">Status Geral</h6>
                      <h3 class="<?= $media_geral_total >= 6 ? 'text-success' : 'text-danger' ?>">
                        <?= $media_geral_total >= 6 ? 'Aprovado' : 'Reprovado' ?>
                      </h3>
                    </div>
                    <div class="align-self-center">
                      <i class="mdi mdi-<?= $media_geral_total >= 6 ? 'check-circle' : 'close-circle' ?> <?= $media_geral_total >= 6 ? 'text-success' : 'text-danger' ?>" style="font-size: 48px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Histórico por Ano -->
          <?php if (empty($historico_por_ano)): ?>
            <div class="row">
              <div class="col-12">
                <div class="card">
                  <div class="card-body">
                    <div class="text-center py-5">
                      <i class="mdi mdi-history" style="font-size: 64px; color: #ccc;"></i>
                      <h5 class="mt-3 text-muted">Nenhum histórico encontrado</h5>
                      <p class="text-muted">Seu histórico escolar aparecerá aqui quando houver notas registradas.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($historico_por_ano as $ano => $dados): ?>
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">
                          <i class="mdi mdi-calendar-year"></i> 
                          Ano Letivo: <?= htmlspecialchars($ano) ?>
                        </h5>
                        <div>
                          <span class="badge badge-info">Turma: <?= htmlspecialchars($dados['turma']) ?></span>
                          <span class="badge badge-<?= $media_geral_por_ano[$ano] >= 6 ? 'success' : 'danger' ?>">
                            Média: <?= $media_geral_por_ano[$ano] ?>
                          </span>
                        </div>
                      </div>
                      
                      <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                          <thead class="thead-light">
                            <tr>
                              <th>Disciplina</th>
                              <th>Professor</th>
                              <th>Carga Horária</th>
                              <th>1ª Unidade</th>
                              <th>2ª Unidade</th>
                              <th>3ª Unidade</th>
                              <th>4ª Unidade</th>
                              <th>Média</th>
                              <th>Status</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($dados['disciplinas'] as $disciplina => $dados_disc): ?>
                              <tr>
                                <td><strong><?= htmlspecialchars($disciplina) ?></strong></td>
                                <td><?= htmlspecialchars($dados_disc['professor']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($dados_disc['carga_horaria']) ?>h</td>
                                <td class="text-center">
                                  <?php if (isset($dados_disc['notas'][1])): ?>
                                    <span class="badge badge-<?= $dados_disc['notas'][1]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                      <?= $dados_disc['notas'][1]['nota'] ?>
                                    </span>
                                  <?php else: ?>
                                    <span class="text-muted">-</span>
                                  <?php endif; ?>
                                </td>
                                <td class="text-center">
                                  <?php if (isset($dados_disc['notas'][2])): ?>
                                    <span class="badge badge-<?= $dados_disc['notas'][2]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                      <?= $dados_disc['notas'][2]['nota'] ?>
                                    </span>
                                  <?php else: ?>
                                    <span class="text-muted">-</span>
                                  <?php endif; ?>
                                </td>
                                <td class="text-center">
                                  <?php if (isset($dados_disc['notas'][3])): ?>
                                    <span class="badge badge-<?= $dados_disc['notas'][3]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                      <?= $dados_disc['notas'][3]['nota'] ?>
                                    </span>
                                  <?php else: ?>
                                    <span class="text-muted">-</span>
                                  <?php endif; ?>
                                </td>
                                <td class="text-center">
                                  <?php if (isset($dados_disc['notas'][4])): ?>
                                    <span class="badge badge-<?= $dados_disc['notas'][4]['nota'] >= 6 ? 'success' : 'danger' ?>">
                                      <?= $dados_disc['notas'][4]['nota'] ?>
                                    </span>
                                  <?php else: ?>
                                    <span class="text-muted">-</span>
                                  <?php endif; ?>
                                </td>
                                <td class="text-center">
                                  <strong class="text-<?= $medias_por_ano[$ano][$disciplina] >= 6 ? 'success' : 'danger' ?>">
                                    <?= $medias_por_ano[$ano][$disciplina] ?>
                                  </strong>
                                </td>
                                <td class="text-center">
                                  <?php if ($medias_por_ano[$ano][$disciplina] >= 6): ?>
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
                              <td colspan="7"><strong>MÉDIA GERAL DO ANO</strong></td>
                              <td class="text-center">
                                <strong class="text-white"><?= $media_geral_por_ano[$ano] ?></strong>
                              </td>
                              <td class="text-center">
                                <span class="badge badge-<?= $media_geral_por_ano[$ano] >= 6 ? 'success' : 'danger' ?>">
                                  <?= $media_geral_por_ano[$ano] >= 6 ? 'APROVADO' : 'REPROVADO' ?>
                                </span>
                              </td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          
          <!-- Informações Adicionais -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">
                    <i class="mdi mdi-information"></i> Informações do Histórico
                  </h5>
                  <div class="row">
                    <div class="col-md-6">
                      <h6>Dados do Aluno</h6>
                      <table class="table table-sm">
                        <tr>
                          <td><strong>Nome:</strong></td>
                          <td><?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></td>
                        </tr>
                        <tr>
                          <td><strong>CPF:</strong></td>
                          <td><?= htmlspecialchars($aluno['cpf']) ?></td>
                        </tr>
                        <tr>
                          <td><strong>Data de Nascimento:</strong></td>
                          <td><?= htmlspecialchars($aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : 'Não informado') ?></td>
                        </tr>
                        <tr>
                          <td><strong>Turma Atual:</strong></td>
                          <td><?= htmlspecialchars($aluno['turma_nome']) ?></td>
                        </tr>
                      </table>
                    </div>
                    <div class="col-md-6">
                      <h6>Resumo Acadêmico</h6>
                      <table class="table table-sm">
                        <tr>
                          <td><strong>Total de Anos:</strong></td>
                          <td><?= count($historico_por_ano) ?></td>
                        </tr>
                        <tr>
                          <td><strong>Disciplinas Cursadas:</strong></td>
                          <td><?= count(array_unique(array_column($historico, 'disciplina_nome'))) ?></td>
                        </tr>
                        <tr>
                          <td><strong>Média Geral:</strong></td>
                          <td><?= $media_geral_total ?></td>
                        </tr>
                        <tr>
                          <td><strong>Status Geral:</strong></td>
                          <td>
                            <span class="badge badge-<?= $media_geral_total >= 6 ? 'success' : 'danger' ?>">
                              <?= $media_geral_total >= 6 ? 'Aprovado' : 'Reprovado' ?>
                            </span>
                          </td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.php -->
        <?php include('../secretaria/partials/_footer.php'); ?>
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
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <!-- endinject -->
  
  <script>
    function gerarPDF() {
      alert('Funcionalidade de geração de PDF será implementada em breve!');
    }
    
    function imprimirHistorico() {
      window.print();
    }
  </script>
</body>

</html>
