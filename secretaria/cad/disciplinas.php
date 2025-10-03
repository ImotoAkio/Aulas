<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// Buscar disciplinas
$disciplinas = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome");
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar disciplinas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Gerenciar Disciplinas</title>
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="shortcut icon" href="../../assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <?php include '../partials/_navbar.php'; ?>
    
    <div class="container-fluid page-body-wrapper">
      <?php include '../partials/_sidebar.php'; ?>
      
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Gerenciar Disciplinas</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Disciplinas</li>
              </ol>
            </nav>
          </div>

          <!-- Mensagens de Feedback -->
          <?php if (isset($_SESSION['erro_disciplina'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= $_SESSION['erro_disciplina'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['erro_disciplina']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['sucesso_disciplina'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $_SESSION['sucesso_disciplina'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['sucesso_disciplina']); ?>
          <?php endif; ?>

          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title">Lista de Disciplinas</h4>
                    <button type="button" class="btn btn-gradient-primary" data-toggle="modal" data-target="#modalDisciplina">
                      <i class="mdi mdi-plus"></i> Nova Disciplina
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Nome</th>
                          <th>Ações</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($disciplinas)): ?>
                          <tr>
                            <td colspan="3" class="text-center text-muted">Nenhuma disciplina cadastrada</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($disciplinas as $disciplina): ?>
                            <tr>
                              <td><?= $disciplina['id'] ?></td>
                              <td><?= htmlspecialchars($disciplina['nome']) ?></td>
                              <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editarDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars($disciplina['nome'], ENT_QUOTES) ?>')">
                                  <i class="mdi mdi-pencil"></i> Editar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="excluirDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars($disciplina['nome'], ENT_QUOTES) ?>')">
                                  <i class="mdi mdi-delete"></i> Excluir
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Adicionar/Editar Disciplina -->
  <div class="modal fade" id="modalDisciplina" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nova Disciplina</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <form id="formDisciplina" method="POST" action="salvar_disciplina.php">
          <div class="modal-body">
            <input type="hidden" id="disciplina_id" name="disciplina_id" value="">
            <div class="form-group">
              <label for="nome">Nome da Disciplina *</label>
              <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-gradient-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmação de Exclusão -->
  <div class="modal fade" id="modalExcluir" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar Exclusão</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Tem certeza que deseja excluir a disciplina <strong id="nomeDisciplinaExcluir"></strong>?</p>
          <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <a href="#" id="btnConfirmarExcluir" class="btn btn-danger">Excluir</a>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/js/off-canvas.js"></script>
  <script src="../../assets/js/misc.js"></script>

  <script>
    function editarDisciplina(id, nome) {
      document.getElementById('modalTitle').textContent = 'Editar Disciplina';
      document.getElementById('disciplina_id').value = id;
      document.getElementById('nome').value = nome;
      $('#modalDisciplina').modal('show');
    }

    function excluirDisciplina(id, nome) {
      document.getElementById('nomeDisciplinaExcluir').textContent = nome;
      document.getElementById('btnConfirmarExcluir').href = 'excluir_disciplina.php?id=' + id;
      $('#modalExcluir').modal('show');
    }

    // Limpar formulário quando modal é fechado
    $('#modalDisciplina').on('hidden.bs.modal', function () {
      document.getElementById('modalTitle').textContent = 'Nova Disciplina';
      document.getElementById('disciplina_id').value = '';
      document.getElementById('nome').value = '';
    });
  </script>
</body>

</html>
