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

// Buscar pareceres do aluno
try {
    $stmt = $pdo->prepare("
        SELECT p.*, d.nome as disciplina_nome, u.nome as professor_nome
        FROM pareceres p
        LEFT JOIN disciplinas d ON p.disciplina_id = d.id
        LEFT JOIN usuarios u ON p.professor_id = u.id
        WHERE p.aluno_id = ?
        ORDER BY p.data_criacao DESC
    ");
    $stmt->execute([$aluno_id]);
    $pareceres = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar pareceres: " . $e->getMessage());
    $pareceres = [];
}

// Buscar disciplinas para filtro
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT d.id, d.nome
        FROM disciplinas d
        INNER JOIN pareceres p ON d.id = p.disciplina_id
        WHERE p.aluno_id = ?
        ORDER BY d.nome
    ");
    $stmt->execute([$aluno_id]);
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    $disciplinas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Pareceres Pedagógicos - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>"
  <!-- endinject -->
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
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-file-document"></i> Pareceres Pedagógicos
                  </h4>
                  <p class="card-description">
                    Acompanhe seus pareceres pedagógicos por disciplina
                  </p>
                  
                  <!-- Filtros -->
                  <div class="row mb-4">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Filtrar por Disciplina</label>
                        <select id="filtro-disciplina" class="form-control">
                          <option value="">Todas as disciplinas</option>
                          <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?= htmlspecialchars($disciplina['nome']) ?>">
                              <?= htmlspecialchars($disciplina['nome']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Filtrar por Unidade</label>
                        <select id="filtro-unidade" class="form-control">
                          <option value="">Todas as unidades</option>
                          <option value="1">1ª Unidade</option>
                          <option value="2">2ª Unidade</option>
                          <option value="3">3ª Unidade</option>
                          <option value="4">4ª Unidade</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" id="limpar-filtros" class="btn btn-outline-secondary btn-block">
                          <i class="mdi mdi-refresh"></i> Limpar Filtros
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <?php if (empty($pareceres)): ?>
                    <div class="text-center py-5">
                      <i class="mdi mdi-file-document-outline" style="font-size: 64px; color: #ccc;"></i>
                      <h5 class="mt-3 text-muted">Nenhum parecer encontrado</h5>
                      <p class="text-muted">Seus pareceres pedagógicos aparecerão aqui quando forem criados pelos professores.</p>
                    </div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Disciplina</th>
                            <th>Unidade</th>
                            <th>Professor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($pareceres as $parecer): ?>
                            <tr class="parecer-item" 
                                data-disciplina="<?= htmlspecialchars($parecer['disciplina_nome']) ?>"
                                data-unidade="<?= htmlspecialchars($parecer['unidade']) ?>">
                              <td>
                                <strong><?= htmlspecialchars($parecer['disciplina_nome']) ?></strong>
                              </td>
                              <td>
                                <span class="badge badge-info"><?= htmlspecialchars($parecer['unidade']) ?>ª Unidade</span>
                              </td>
                              <td><?= htmlspecialchars($parecer['professor_nome']) ?></td>
                              <td>
                                <?= date('d/m/Y', strtotime($parecer['data_criacao'])) ?>
                              </td>
                              <td>
                                <?php if ($parecer['status'] === 'aprovado'): ?>
                                  <span class="badge badge-success">Aprovado</span>
                                <?php elseif ($parecer['status'] === 'pendente'): ?>
                                  <span class="badge badge-warning">Pendente</span>
                                <?php else: ?>
                                  <span class="badge badge-secondary">Rascunho</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="visualizarParecer(<?= $parecer['id'] ?>)">
                                  <i class="mdi mdi-eye"></i> Visualizar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" 
                                        onclick="downloadParecer(<?= $parecer['id'] ?>)">
                                  <i class="mdi mdi-download"></i> PDF
                                </button>
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

  <!-- Modal para visualizar parecer -->
  <div class="modal fade" id="modalParecer" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="mdi mdi-file-document"></i> Parecer Pedagógico
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="conteudoParecer">
          <!-- Conteúdo será carregado via AJAX -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-primary" onclick="imprimirParecer()">
            <i class="mdi mdi-printer"></i> Imprimir
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- plugins:js -->
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
  <!-- endinject -->
  
  <script>
    // Filtros
    $(document).ready(function() {
      $('#filtro-disciplina, #filtro-unidade').on('change', function() {
        filtrarPareceres();
      });
      
      $('#limpar-filtros').on('click', function() {
        $('#filtro-disciplina, #filtro-unidade').val('');
        filtrarPareceres();
      });
    });
    
    function filtrarPareceres() {
      var disciplina = $('#filtro-disciplina').val();
      var unidade = $('#filtro-unidade').val();
      
      $('.parecer-item').each(function() {
        var $row = $(this);
        var disciplinaRow = $row.data('disciplina');
        var unidadeRow = $row.data('unidade');
        
        var mostrar = true;
        
        if (disciplina && disciplinaRow !== disciplina) {
          mostrar = false;
        }
        
        if (unidade && unidadeRow !== unidade) {
          mostrar = false;
        }
        
        if (mostrar) {
          $row.show();
        } else {
          $row.hide();
        }
      });
    }
    
    function visualizarParecer(parecerId) {
      // Simular carregamento do parecer
      $('#conteudoParecer').html(`
        <div class="text-center">
          <div class="spinner-border" role="status">
            <span class="sr-only">Carregando...</span>
          </div>
          <p class="mt-2">Carregando parecer...</p>
        </div>
      `);
      
      $('#modalParecer').modal('show');
      
      // Simular dados do parecer (em uma implementação real, seria via AJAX)
      setTimeout(function() {
        $('#conteudoParecer').html(`
          <div class="parecer-content">
            <div class="row">
              <div class="col-md-12">
                <h6 class="text-primary">Informações Gerais</h6>
                <table class="table table-sm">
                  <tr>
                    <td><strong>Aluno:</strong></td>
                    <td><?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Turma:</strong></td>
                    <td><?= htmlspecialchars($aluno['turma_nome']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Ano Letivo:</strong></td>
                    <td><?= htmlspecialchars($aluno['ano_letivo']) ?></td>
                  </tr>
                </table>
              </div>
            </div>
            
            <div class="row mt-3">
              <div class="col-md-12">
                <h6 class="text-primary">Avaliação do Desempenho</h6>
                <p>O aluno demonstrou bom desenvolvimento durante esta unidade, 
                participando ativamente das atividades propostas e demonstrando 
                interesse pelo conteúdo abordado.</p>
                
                <h6 class="text-primary">Pontos Positivos</h6>
                <ul>
                  <li>Participação ativa em sala de aula</li>
                  <li>Boa organização dos materiais</li>
                  <li>Interesse pelos temas abordados</li>
                </ul>
                
                <h6 class="text-primary">Sugestões para Melhoria</h6>
                <ul>
                  <li>Dedicar mais tempo aos estudos em casa</li>
                  <li>Participar mais dos trabalhos em grupo</li>
                </ul>
              </div>
            </div>
          </div>
        `);
      }, 1000);
    }
    
    function downloadParecer(parecerId) {
      window.open('download_parecer.php?id=' + parecerId, '_blank');
    }
    
    function imprimirParecer() {
      var conteudo = $('#conteudoParecer').html();
      var janela = window.open('', '_blank');
      janela.document.write(`
        <html>
          <head>
            <title>Parecer Pedagógico</title>
            <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>"
            <style>
              body { font-family: Arial, sans-serif; }
              .parecer-content { padding: 20px; }
            </style>
          </head>
          <body>
            <div class="parecer-content">
              ${conteudo}
            </div>
          </body>
        </html>
      `);
      janela.document.close();
      janela.print();
    }
  </script>
</body>

</html>
