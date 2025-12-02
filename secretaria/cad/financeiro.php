<?php
// Garantir utilitários disponíveis
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../../config/database.php';
}

session_start();
// Somente coordenador pode cadastrar usuários financeiros
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'coordenador') {
  require_once __DIR__ . '/../../config/database.php';
  redirectTo('login.php');
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $conf = $_POST['confirmar'] ?? '';

  if ($nome === '' || $email === '' || $senha === '' || $conf === '') {
    $erro = 'Preencha todos os campos.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erro = 'Email inválido.';
  } elseif ($senha !== $conf) {
    $erro = 'As senhas não coincidem.';
  } else {
    try {
      // Verificar se já existe
      $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
      $stmt->execute([':email' => $email]);
      if ($stmt->fetch()) {
        $erro = 'Já existe um usuário com este email.';
      } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmtIns = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, tipo) VALUES (:nome, :email, :senha, :tipo)');
        $stmtIns->execute([
          ':nome' => $nome,
          ':email' => $email,
          ':senha' => $hash,
          ':tipo' => 'financeiro',
        ]);
        $sucesso = 'Usuário financeiro criado com sucesso!';
      }
    } catch (PDOException $e) {
      error_log('Erro ao criar usuário financeiro: ' . $e->getMessage());
      $erro = 'Erro ao salvar. Tente novamente.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cadastro - Usuário Financeiro</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/ti-icons/css/themify-icons.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>">
  <!-- endinject -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>">
</head>

<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/../partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/../partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Cadastrar Usuário Financeiro</h3>
          </div>

          <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
          <?php endif; ?>
          <?php if ($sucesso): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
          <?php endif; ?>

          <div class="card mb-4">
            <div class="card-body">
              <h4 class="card-title">Novo Usuário</h4>
              <form method="post" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nome</label>
                  <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Senha</label>
                  <input type="password" name="senha" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Confirmar Senha</label>
                  <input type="password" name="confirmar" class="form-control" required>
                </div>
                <div class="col-12 mt-3">
                  <button type="submit" class="btn btn-gradient-primary">Salvar</button>
                </div>
              </form>
            </div>
          </div>

          <?php
          // Buscar usuários financeiros
          try {
            $stmtList = $pdo->query("SELECT * FROM usuarios WHERE tipo = 'financeiro' ORDER BY nome");
            $usuarios = $stmtList->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOException $e) {
            error_log('Erro ao buscar usuários: ' . $e->getMessage());
            $usuarios = [];
          }
          ?>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Usuários Financeiros</h4>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Nome</th>
                      <th>Email</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($usuarios)): ?>
                      <tr>
                        <td colspan="3" class="text-center">Nenhum usuário encontrado.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($usuarios as $u): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($u['nome']); ?></td>
                          <td><?php echo htmlspecialchars($u['email']); ?></td>
                          <td>
                            <a href="editar_financeiro.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-info">
                              <i class="mdi mdi-pencil"></i>
                            </a>
                            <a href="excluir_financeiro.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger"
                              onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                              <i class="mdi mdi-delete"></i>
                            </a>
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
        <?php include __DIR__ . '/../partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <!-- plugins:js -->
  <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/jquery.cookie.js'); ?>"></script>
  <!-- endinject -->
</body>

</html>