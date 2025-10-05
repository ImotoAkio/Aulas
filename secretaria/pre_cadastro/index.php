<?php
require_once '../../config/database.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

// Buscar pré-cadastros pendentes
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.codigo_pre_cadastro, a.status_cadastro, a.preenchido_por_responsavel, 
               a.dados_preenchidos_em, t.nome as turma_nome, u.nome as criado_por_nome,
               pc.criado_em, pc.link_expiracao, pc.observacoes
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        LEFT JOIN usuarios u ON pc.criado_por = u.id
        WHERE a.status_cadastro IN ('pre_cadastro', 'completo')
        ORDER BY a.criado_em DESC
    ");
    $stmt->execute();
    $pre_cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pre_cadastros = [];
    error_log("Erro ao buscar pré-cadastros: " . $e->getMessage());
}

// Buscar turmas para o formulário
try {
    $stmt = $pdo->prepare("SELECT id, nome FROM turmas ORDER BY nome");
    $stmt->execute();
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $turmas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-cadastros de Alunos - Secretaria</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
</head>
<body>
    <div class="container-scroller">
        <?php include '../partials/_navbar.php'; ?>
        
        <div class="container-fluid page-body-wrapper">
            <?php include '../partials/_sidebar.php'; ?>
            
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">
                            <span class="page-title-icon bg-gradient-primary text-white me-2">
                                <i class="mdi mdi-account-plus"></i>
                            </span>
                            Pré-cadastros de Alunos
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Pré-cadastros</li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Botão para criar novo pré-cadastro -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#modalNovoPreCadastro">
                                <i class="mdi mdi-plus"></i> Novo Pré-cadastro
                            </button>
                        </div>
                    </div>

                    <!-- Cards de resumo -->
                    <div class="row">
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Pendentes</h4>
                                    <h2 class="text-warning mb-0"><?php echo count(array_filter($pre_cadastros, function($p) { return $p['status_cadastro'] === 'pre_cadastro'; })); ?></h2>
                                    <small class="text-muted">Aguardando preenchimento</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Completos</h4>
                                    <h2 class="text-info mb-0"><?php echo count(array_filter($pre_cadastros, function($p) { return $p['status_cadastro'] === 'completo'; })); ?></h2>
                                    <small class="text-muted">Aguardando aprovação</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Aprovados</h4>
                                    <h2 class="text-success mb-0"><?php echo count(array_filter($pre_cadastros, function($p) { return $p['status_cadastro'] === 'aprovado'; })); ?></h2>
                                    <small class="text-muted">Matriculados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Total</h4>
                                    <h2 class="text-primary mb-0"><?php echo count($pre_cadastros); ?></h2>
                                    <small class="text-muted">Pré-cadastros</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de pré-cadastros -->
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-format-list-bulleted text-info me-2"></i>
                                        Lista de Pré-cadastros
                                    </h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Aluno</th>
                                                    <th>Turma</th>
                                                    <th>Status</th>
                                                    <th>Criado por</th>
                                                    <th>Data Criação</th>
                                                    <th>Preenchido</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pre_cadastros as $pre_cadastro): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($pre_cadastro['nome']); ?></strong>
                                                        <?php if ($pre_cadastro['codigo_pre_cadastro']): ?>
                                                        <br><small class="text-muted">Código: <?php echo htmlspecialchars($pre_cadastro['codigo_pre_cadastro']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pre_cadastro['turma_nome'] ?? 'Não definida'); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        $status_text = '';
                                                        switch ($pre_cadastro['status_cadastro']) {
                                                            case 'pre_cadastro':
                                                                $status_class = 'badge-warning';
                                                                $status_text = 'Pendente';
                                                                break;
                                                            case 'completo':
                                                                $status_class = 'badge-info';
                                                                $status_text = 'Completo';
                                                                break;
                                                            case 'aprovado':
                                                                $status_class = 'badge-success';
                                                                $status_text = 'Aprovado';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pre_cadastro['criado_por_nome'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($pre_cadastro['criado_em'])); ?></td>
                                                    <td>
                                                        <?php if ($pre_cadastro['preenchido_por_responsavel']): ?>
                                                            <span class="badge badge-success">Sim</span>
                                                            <br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($pre_cadastro['dados_preenchidos_em'])); ?></small>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Não</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="visualizar.php?id=<?php echo $pre_cadastro['id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="mdi mdi-eye"></i>
                                                            </a>
                                                            <?php if ($pre_cadastro['status_cadastro'] === 'completo'): ?>
                                                            <a href="aprovar.php?id=<?php echo $pre_cadastro['id']; ?>" class="btn btn-sm btn-outline-success">
                                                                <i class="mdi mdi-check"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                            <?php if ($pre_cadastro['codigo_pre_cadastro']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="copiarLink('<?php echo $pre_cadastro['codigo_pre_cadastro']; ?>')">
                                                                <i class="mdi mdi-link"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para novo pré-cadastro -->
    <div class="modal fade" id="modalNovoPreCadastro" tabindex="-1" aria-labelledby="modalNovoPreCadastroLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovoPreCadastroLabel">Novo Pré-cadastro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="criar.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Aluno *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="turma_id" class="form-label">Turma Sugerida</label>
                            <select class="form-control" id="turma_id" name="turma_id">
                                <option value="">Selecione uma turma</option>
                                <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Informações adicionais sobre o aluno..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Pré-cadastro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copiarLink(codigo) {
            const link = '<?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=' + codigo;
            navigator.clipboard.writeText(link).then(function() {
                alert('Link copiado para a área de transferência!');
            });
        }
    </script>
</body>
</html>
