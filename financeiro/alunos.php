<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
}

// Parâmetros de filtro
$filtro_nome = $_GET['nome'] ?? '';
$filtro_turma = $_GET['turma'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_nome)) {
    $where_conditions[] = "(a.nome LIKE ? OR a.nome_completo LIKE ?)";
    $params[] = "%$filtro_nome%";
    $params[] = "%$filtro_nome%";
}

if (!empty($filtro_turma)) {
    $where_conditions[] = "a.turma_id = ?";
    $params[] = $filtro_turma;
}

if (!empty($filtro_status)) {
    $where_conditions[] = "a.status_cadastro = ?";
    $params[] = $filtro_status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar alunos com JOIN para obter nome da turma
$alunos = [];
try {
    $sql = "
        SELECT a.id, a.nome, a.nome_completo, a.turma_id, a.status_cadastro, 
               t.nome as turma_nome, t.ano_letivo
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        $where_clause
        ORDER BY a.nome
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar alunos: " . $e->getMessage());
    $alunos = [];
}

// Buscar turmas para o filtro
$turmas = [];
try {
    $stmt = $pdo->query("SELECT id, nome, ano_letivo FROM turmas ORDER BY nome");
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());
    $turmas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Alunos</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
</head>
<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Alunos</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Alunos</li>
              </ol>
            </nav>
          </div>

          <!-- Filtros -->
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">Filtros</h5>
              <form method="GET" class="row g-3">
                <div class="col-md-4">
                  <label for="nome" class="form-label">Nome do Aluno</label>
                  <input type="text" class="form-control" id="nome" name="nome" 
                         value="<?php echo htmlspecialchars($filtro_nome); ?>" 
                         placeholder="Digite o nome completo do aluno...">
                </div>
                <div class="col-md-3">
                  <label for="turma" class="form-label">Turma</label>
                  <select class="form-control" id="turma" name="turma">
                    <option value="">Todas as turmas</option>
                    <?php foreach ($turmas as $turma): ?>
                      <option value="<?php echo $turma['id']; ?>" 
                              <?php echo ($filtro_turma == $turma['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($turma['nome'] . ' (' . $turma['ano_letivo'] . ')'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="status" class="form-label">Status</label>
                  <select class="form-control" id="status" name="status">
                    <option value="">Todos os status</option>
                    <option value="pre_cadastro" <?php echo ($filtro_status == 'pre_cadastro') ? 'selected' : ''; ?>>Pré-cadastro</option>
                    <option value="completo" <?php echo ($filtro_status == 'completo') ? 'selected' : ''; ?>>Completo</option>
                    <option value="aprovado" <?php echo ($filtro_status == 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">&nbsp;</label>
                  <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?php echo getPageUrl('financeiro/alunos.php'); ?>" class="btn btn-outline-secondary">Limpar</a>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Tabela de Alunos -->
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Lista de Alunos</h5>
                <span class="badge badge-info"><?php echo count($alunos); ?> aluno(s) encontrado(s)</span>
              </div>
              
              <?php if (empty($alunos)): ?>
                <div class="alert alert-info text-center">
                  <i class="mdi mdi-information-outline"></i>
                  Nenhum aluno encontrado com os filtros aplicados.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                      <tr>
                        <th>Nome Completo</th>
                        <th>Turma</th>
                        <th>Status</th>
                        <th>Ações</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($alunos as $aluno): ?>
                        <tr>
                          <td>
                            <strong><?php echo htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']); ?></strong>
                          </td>
                          <td>
                            <?php if ($aluno['turma_nome']): ?>
                              <span class="badge badge-primary">
                                <?php echo htmlspecialchars($aluno['turma_nome']); ?>
                                <?php if ($aluno['ano_letivo']): ?>
                                  <small>(<?php echo $aluno['ano_letivo']; ?>)</small>
                                <?php endif; ?>
                              </span>
                            <?php else: ?>
                              <span class="text-muted">Sem turma</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($aluno['status_cadastro']) {
                                case 'pre_cadastro':
                                    $status_class = 'badge-warning';
                                    $status_text = 'Pré-cadastro';
                                    break;
                                case 'completo':
                                    $status_class = 'badge-info';
                                    $status_text = 'Completo';
                                    break;
                                case 'aprovado':
                                    $status_class = 'badge-success';
                                    $status_text = 'Aprovado';
                                    break;
                                default:
                                    $status_class = 'badge-secondary';
                                    $status_text = 'Não definido';
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                          </td>
                          <td>
                            <div class="btn-group" role="group">
                              <a class="btn btn-sm btn-outline-primary" 
                                 href="<?php echo getPageUrl('financeiro/aluno_detalhe.php?id=' . $aluno['id']); ?>" 
                                 title="Ver detalhes">
                                <i class="mdi mdi-eye"></i>
                              </a>
                              <a class="btn btn-sm btn-outline-info" 
                                 href="<?php echo getPageUrl('financeiro/cad/visualizar_aluno.php?id=' . $aluno['id']); ?>" 
                                 title="Visualizar cadastro">
                                <i class="mdi mdi-account-details"></i>
                              </a>
                              <a class="btn btn-sm btn-outline-warning" 
                                 href="<?php echo getPageUrl('financeiro/cad/editar_aluno.php?id=' . $aluno['id']); ?>" 
                                 title="Editar">
                                <i class="mdi mdi-pencil"></i>
                              </a>
                            </div>
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
        <?php include __DIR__ . '/partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
  
  <script>
    $(document).ready(function() {
      // Inicializar collapse para sidebar
      $('[data-toggle="collapse"], [data-bs-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $(target).collapse('toggle');
      });
      
      // Prevenir comportamento padrão de links com href="#"
      $('.nav-link[href="#"]').on('click', function(e) {
        e.preventDefault();
      });
      
      // Garantir que os dropdowns funcionem
      $('.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).next('.dropdown-menu').toggle();
      });
    });
  </script>
</body>
</html>


