<?php
// Garantir utilitários disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../../config/database.php';
}

session_start();
// Somente coordenador pode editar
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'coordenador') {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('login.php');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: financeiro.php');
    exit;
}

$erro = '';
$sucesso = '';

// Buscar dados do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id AND tipo = 'financeiro'");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: financeiro.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Erro ao buscar usuário: ' . $e->getMessage());
    header('Location: financeiro.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $conf = $_POST['confirmar'] ?? '';

    if ($nome === '' || $email === '') {
        $erro = 'Preencha nome e email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } elseif ($senha !== '' && $senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        try {
            // Verificar email duplicado (exceto o próprio)
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
            $stmtCheck->execute([':email' => $email, ':id' => $id]);
            if ($stmtCheck->fetch()) {
                $erro = 'Este email já está em uso por outro usuário.';
            } else {
                if ($senha !== '') {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmtUpd = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id");
                    $stmtUpd->execute([
                        ':nome' => $nome,
                        ':email' => $email,
                        ':senha' => $hash,
                        ':id' => $id
                    ]);
                } else {
                    $stmtUpd = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id");
                    $stmtUpd->execute([
                        ':nome' => $nome,
                        ':email' => $email,
                        ':id' => $id
                    ]);
                }
                $sucesso = 'Usuário atualizado com sucesso!';
                // Atualizar dados exibidos
                $usuario['nome'] = $nome;
                $usuario['email'] = $email;
            }
        } catch (PDOException $e) {
            error_log('Erro ao atualizar usuário: ' . $e->getMessage());
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
    <title>Editar Usuário Financeiro</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
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
                        <h3 class="page-title">Editar Usuário Financeiro</h3>
                    </div>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="post" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome</label>
                                    <input type="text" name="nome" class="form-control"
                                        value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nova Senha (deixe em branco para manter)</label>
                                    <input type="password" name="senha" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" name="confirmar" class="form-control">
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-gradient-primary">Salvar Alterações</button>
                                    <a href="financeiro.php" class="btn btn-light">Voltar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php include __DIR__ . '/../partials/_footer.php'; ?>
            </div>
        </div>
    </div>
    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
</body>

</html>