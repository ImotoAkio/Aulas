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

$turma_id = $_GET['id'] ?? null;
if (!$turma_id || !is_numeric($turma_id)) {
    $_SESSION['erro'] = "ID da turma inválido.";
    header('Location: turmas.php');
    exit();
}

// Buscar dados da turma
$turma = null;
try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               COUNT(a.id) as total_alunos
        FROM turmas t 
        LEFT JOIN alunos a ON t.id = a.turma_id 
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$turma_id]);
    $turma = $stmt->fetch();
    
    if (!$turma) {
        $_SESSION['erro'] = "Turma não encontrada.";
        header('Location: turmas.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar turma: " . $e->getMessage());
    $_SESSION['erro'] = "Erro interno do sistema.";
    header('Location: turmas.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $ano_letivo = trim($_POST['ano_letivo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Validações
    if (empty($nome)) {
        $erro = 'Nome da turma é obrigatório.';
    } elseif (empty($ano_letivo)) {
        $erro = 'Ano letivo é obrigatório.';
    } elseif (!is_numeric($ano_letivo) || $ano_letivo < 2020 || $ano_letivo > 2030) {
        $erro = 'Ano letivo deve ser um número entre 2020 e 2030.';
    } else {
        try {
            // Verificar se já existe outra turma com mesmo nome e ano
            $stmt = $pdo->prepare("SELECT id FROM turmas WHERE nome = ? AND ano_letivo = ? AND id != ?");
            $stmt->execute([$nome, $ano_letivo, $turma_id]);
            
            if ($stmt->fetch()) {
                $erro = 'Já existe uma turma com este nome no ano letivo ' . $ano_letivo . '.';
            } else {
                // Atualizar turma
                $stmt = $pdo->prepare("UPDATE turmas SET nome = ?, ano_letivo = ?, descricao = ? WHERE id = ?");
                $stmt->execute([$nome, $ano_letivo, $descricao, $turma_id]);
                
                $_SESSION['sucesso'] = 'Turma "' . $nome . '" atualizada com sucesso!';
                header('Location: turmas.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar turma: " . $e->getMessage());
            $erro = 'Erro interno do sistema. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Editar Turma - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
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
            <h3 class="page-title">Editar Turma</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href='<?php echo getPageUrl("secretaria/index.php"); ?>'>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="turmas.php">Turmas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
              </ol>
            </nav>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-school"></i> Editar Turma: <?= htmlspecialchars($turma['nome']) ?>
                  </h4>
                  <p class="card-description">
                    Atualize os dados da turma
                  </p>

                  <?php if ($erro): ?>
                    <div class="alert alert-danger">
                      <i class="mdi mdi-alert-circle"></i>
                      <?= htmlspecialchars($erro) ?>
                    </div>
                  <?php endif; ?>

                  <!-- Informações da turma -->
                  <div class="alert alert-info">
                    <h6><i class="mdi mdi-information"></i> Informações da Turma</h6>
                    <p class="mb-0">
                      <strong>Alunos cadastrados:</strong> <?= $turma['total_alunos'] ?> aluno(s)
                      <?php if ($turma['total_alunos'] > 0): ?>
                        <br><small class="text-muted">A alteração do nome ou ano letivo pode afetar os alunos já cadastrados.</small>
                      <?php endif; ?>
                    </p>
                  </div>

                  <form method="POST" class="forms-sample">
                    <div class="form-group">
                      <label for="nome">Nome da Turma *</label>
                      <input type="text" 
                             class="form-control" 
                             id="nome" 
                             name="nome" 
                             placeholder="Ex: 1° ANO, 2° ANO, G2/G3" 
                             value="<?= htmlspecialchars($_POST['nome'] ?? $turma['nome']) ?>"
                             required>
                      <small class="form-text text-muted">Nome identificador da turma</small>
                    </div>

                    <div class="form-group">
                      <label for="ano_letivo">Ano Letivo *</label>
                      <input type="number" 
                             class="form-control" 
                             id="ano_letivo" 
                             name="ano_letivo" 
                             placeholder="2025" 
                             min="2020" 
                             max="2030"
                             value="<?= htmlspecialchars($_POST['ano_letivo'] ?? $turma['ano_letivo']) ?>"
                             required>
                      <small class="form-text text-muted">Ano letivo da turma</small>
                    </div>

                    <div class="form-group">
                      <label for="descricao">Descrição</label>
                      <textarea class="form-control" 
                                id="descricao" 
                                name="descricao" 
                                rows="3" 
                                placeholder="Descrição opcional da turma"><?= htmlspecialchars($_POST['descricao'] ?? $turma['descricao']) ?></textarea>
                      <small class="form-text text-muted">Informações adicionais sobre a turma</small>
                    </div>

                    <div class="form-group">
                      <button type="submit" class="btn btn-primary mr-2">
                        <i class="mdi mdi-content-save"></i> Salvar Alterações
                      </button>
                      <a href="turmas.php" class="btn btn-light">
                        <i class="mdi mdi-close"></i> Cancelar
                      </a>
                    </div>
                  </form>
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
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- inject:js -->
  <script src="../../assets/js/off-canvas.js"></script>
  <script src="../../assets/js/misc.js"></script>
  <script src="../../assets/js/settings.js"></script>
  <script src="../../assets/js/todolist.js"></script>
  <script src="../../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
</body>

</html>
