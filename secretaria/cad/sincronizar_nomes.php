<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getPageUrl')) {
    require_once __DIR__ . '/../../config/database.php';
}

session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('login.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sincronizar'])) {
    try {
        $pdo->beginTransaction();
        
        // Buscar alunos que precisam de sincronização
        // Caso 1: nome_completo preenchido mas nome vazio ou diferente
        $stmt = $pdo->query("
            SELECT id, nome, nome_completo 
            FROM alunos 
            WHERE nome_completo IS NOT NULL 
            AND nome_completo != '' 
            AND (nome != nome_completo OR nome IS NULL OR nome = '')
        ");
        $alunos_caso1 = $stmt->fetchAll();
        
        // Caso 2: nome preenchido mas nome_completo vazio
        $stmt = $pdo->query("
            SELECT id, nome, nome_completo 
            FROM alunos 
            WHERE nome IS NOT NULL 
            AND nome != '' 
            AND (nome_completo IS NULL OR nome_completo = '')
        ");
        $alunos_caso2 = $stmt->fetchAll();
        
        $total_atualizados = 0;
        
        // Atualizar caso 1: nome_completo → nome
        foreach ($alunos_caso1 as $aluno) {
            $stmt = $pdo->prepare("UPDATE alunos SET nome = ? WHERE id = ?");
            $stmt->execute([$aluno['nome_completo'], $aluno['id']]);
            $total_atualizados++;
        }
        
        // Atualizar caso 2: nome → nome_completo
        foreach ($alunos_caso2 as $aluno) {
            $stmt = $pdo->prepare("UPDATE alunos SET nome_completo = ? WHERE id = ?");
            $stmt->execute([$aluno['nome'], $aluno['id']]);
            $total_atualizados++;
        }
        
        $pdo->commit();
        $mensagem = "Sincronização concluída! {$total_atualizados} alunos foram atualizados.";
        $tipo_mensagem = "success";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro na sincronização: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar estatísticas
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN nome_completo IS NOT NULL AND nome_completo != '' THEN 1 ELSE 0 END) as com_nome_completo,
            SUM(CASE WHEN nome IS NOT NULL AND nome != '' THEN 1 ELSE 0 END) as com_nome,
            SUM(CASE WHEN nome_completo IS NOT NULL AND nome_completo != '' AND (nome != nome_completo OR nome IS NULL OR nome = '') THEN 1 ELSE 0 END) as caso1,
            SUM(CASE WHEN nome IS NOT NULL AND nome != '' AND (nome_completo IS NULL OR nome_completo = '') THEN 1 ELSE 0 END) as caso2,
            SUM(CASE WHEN nome_completo IS NOT NULL AND nome_completo != '' AND nome IS NOT NULL AND nome != '' AND nome = nome_completo THEN 1 ELSE 0 END) as ja_sincronizados
        FROM alunos
    ");
    $stats_raw = $stmt->fetch();
    
    $stats = [
        'total' => $stats_raw['total'],
        'com_nome_completo' => $stats_raw['com_nome_completo'],
        'com_nome' => $stats_raw['com_nome'],
        'para_sincronizar' => $stats_raw['caso1'] + $stats_raw['caso2'],
        'caso1' => $stats_raw['caso1'],
        'caso2' => $stats_raw['caso2'],
        'ja_sincronizados' => $stats_raw['ja_sincronizados']
    ];
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        'com_nome_completo' => 0,
        'com_nome' => 0,
        'para_sincronizar' => 0,
        'caso1' => 0,
        'caso2' => 0,
        'ja_sincronizados' => 0
    ];
}

// Buscar exemplos de alunos que precisam ser sincronizados
try {
    // Caso 1: nome_completo preenchido mas nome vazio ou diferente
    $stmt = $pdo->query("
        SELECT id, nome, nome_completo, 'caso1' as tipo
        FROM alunos 
        WHERE nome_completo IS NOT NULL 
        AND nome_completo != '' 
        AND (nome != nome_completo OR nome IS NULL OR nome = '')
        LIMIT 5
    ");
    $exemplos_caso1 = $stmt->fetchAll();
    
    // Caso 2: nome preenchido mas nome_completo vazio
    $stmt = $pdo->query("
        SELECT id, nome, nome_completo, 'caso2' as tipo
        FROM alunos 
        WHERE nome IS NOT NULL 
        AND nome != '' 
        AND (nome_completo IS NULL OR nome_completo = '')
        LIMIT 5
    ");
    $exemplos_caso2 = $stmt->fetchAll();
    
    $exemplos = array_merge($exemplos_caso1, $exemplos_caso2);
} catch (PDOException $e) {
    $exemplos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Sincronizar Nomes - Rosa de Sharom</title>
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
            <h3 class="page-title">Sincronizar Nomes</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href='<?php echo getPageUrl("secretaria/index.php"); ?>'>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sincronizar Nomes</li>
              </ol>
            </nav>
          </div>

          <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
              <i class="mdi mdi-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
              <?= htmlspecialchars($mensagem) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Estatísticas -->
          <div class="row">
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $stats['total'] ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
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
                        <h3 class="mb-0"><?= $stats['com_nome'] ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
                        <span class="mdi mdi-account icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Com Nome</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $stats['para_sincronizar'] ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-warning">
                        <span class="mdi mdi-sync icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Para Sincronizar</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $stats['ja_sincronizados'] ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-check-circle icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Já Sincronizados</h6>
                </div>
              </div>
            </div>
          </div>

          <!-- Ação de Sincronização -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-sync"></i> Sincronizar Campos Nome
                  </h4>
                  <p class="card-description">
                    Esta operação irá sincronizar os campos 'nome' e 'nome_completo':
                    <br>• Se 'nome_completo' estiver preenchido e 'nome' vazio → copia 'nome_completo' para 'nome'
                    <br>• Se 'nome' estiver preenchido e 'nome_completo' vazio → copia 'nome' para 'nome_completo'
                  </p>
                  
                  <?php if ($stats['para_sincronizar'] > 0): ?>
                    <div class="alert alert-warning">
                      <h6><i class="mdi mdi-alert"></i> Atenção!</h6>
                      <p class="mb-0">
                        <strong><?= $stats['para_sincronizar'] ?></strong> alunos serão atualizados. 
                        Esta operação não pode ser desfeita.
                      </p>
                    </div>
                    
                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja sincronizar os nomes? Esta operação não pode ser desfeita.')">
                      <button type="submit" name="sincronizar" class="btn btn-warning">
                        <i class="mdi mdi-sync"></i> Sincronizar Nomes (<?= $stats['para_sincronizar'] ?> alunos)
                      </button>
                    </form>
                  <?php else: ?>
                    <div class="alert alert-success">
                      <h6><i class="mdi mdi-check-circle"></i> Sincronização Completa!</h6>
                      <p class="mb-0">Todos os alunos já estão com os campos 'nome' e 'nome_completo' sincronizados.</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Exemplos de Alunos -->
          <?php if (!empty($exemplos)): ?>
            <div class="row">
              <div class="col-12">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-format-list-bulleted"></i> Exemplos de Alunos para Sincronizar
                    </h4>
                    <p class="card-description">
                      Mostrando os primeiros 10 alunos que serão atualizados:
                    </p>
                    
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead>
                          <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Nome Completo</th>
                            <th>Tipo</th>
                            <th>Ação</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($exemplos as $aluno): ?>
                            <tr>
                              <td><?= $aluno['id'] ?></td>
                              <td>
                                <?php if (empty($aluno['nome'])): ?>
                                  <span class="text-muted">Vazio</span>
                                <?php else: ?>
                                  <?= htmlspecialchars($aluno['nome']) ?>
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php if (empty($aluno['nome_completo'])): ?>
                                  <span class="text-muted">Vazio</span>
                                <?php else: ?>
                                  <?= htmlspecialchars($aluno['nome_completo']) ?>
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php if ($aluno['tipo'] == 'caso1'): ?>
                                  <span class="badge badge-info">
                                    <i class="mdi mdi-arrow-right"></i> nome_completo → nome
                                  </span>
                                <?php else: ?>
                                  <span class="badge badge-warning">
                                    <i class="mdi mdi-arrow-left"></i> nome → nome_completo
                                  </span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <span class="badge badge-success">
                                  <i class="mdi mdi-sync"></i> Será sincronizado
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
            </div>
          <?php endif; ?>

          <!-- Informações Técnicas -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-information"></i> Informações Técnicas
                  </h4>
                  
                  <div class="row">
                    <div class="col-md-6">
                      <h6>O que esta operação faz:</h6>
                      <ul>
                        <li><strong>Caso 1:</strong> Copia <code>nome_completo</code> → <code>nome</code></li>
                        <li><strong>Caso 2:</strong> Copia <code>nome</code> → <code>nome_completo</code></li>
                        <li>Mantém a integridade dos dados existentes</li>
                        <li>Operação executada em transação (tudo ou nada)</li>
                      </ul>
                    </div>
                    <div class="col-md-6">
                      <h6>Critérios de sincronização:</h6>
                      <ul>
                        <li><strong>Caso 1:</strong> <code>nome_completo</code> preenchido + <code>nome</code> vazio/diferente</li>
                        <li><strong>Caso 2:</strong> <code>nome</code> preenchido + <code>nome_completo</code> vazio</li>
                        <li>Não altera registros já sincronizados</li>
                        <li>Prioriza dados mais completos</li>
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
