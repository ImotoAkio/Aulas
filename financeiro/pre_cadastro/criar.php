<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_cadastro = $_POST['tipo_cadastro'] ?? 'novo';
    $nome = trim($_POST['nome'] ?? '');
    $turma_id = !empty($_POST['turma_id']) ? (int)$_POST['turma_id'] : null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $aluno_existente_id = !empty($_POST['aluno_existente_id']) ? (int)$_POST['aluno_existente_id'] : null;
    
    if (empty($nome)) {
        $erro = "Nome do aluno é obrigatório.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Gerar código único para o link
            $codigo = substr(md5(uniqid() . time()), 0, 20);
            
            if ($tipo_cadastro === 'existente' && $aluno_existente_id) {
                // Re-matrícula: atualizar aluno existente
                $stmt = $pdo->prepare("
                    UPDATE alunos 
                    SET status_cadastro = 'pre_cadastro', codigo_pre_cadastro = ?, turma_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$codigo, $turma_id, $aluno_existente_id]);
                $aluno_id = $aluno_existente_id;
            } else {
                // Novo aluno: inserir novo registro
                $stmt = $pdo->prepare("
                    INSERT INTO alunos (nome, turma_id, status_cadastro, codigo_pre_cadastro) 
                    VALUES (?, ?, 'pre_cadastro', ?)
                ");
                $stmt->execute([$nome, $turma_id, $codigo]);
                $aluno_id = $pdo->lastInsertId();
            }
            
            // Inserir/atualizar registro de controle
            $stmt = $pdo->prepare("
                INSERT INTO pre_cadastros_controle (aluno_id, codigo_link, criado_por, link_expiracao, observacoes, tipo_cadastro) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?)
                ON DUPLICATE KEY UPDATE 
                codigo_link = VALUES(codigo_link),
                criado_por = VALUES(criado_por),
                link_expiracao = VALUES(link_expiracao),
                observacoes = VALUES(observacoes),
                tipo_cadastro = VALUES(tipo_cadastro)
            ");
            $stmt->execute([$aluno_id, $codigo, $_SESSION['usuario_id'], $observacoes, $tipo_cadastro]);
            
            $pdo->commit();
            
            // Redirecionar com sucesso
            redirectTo('financeiro/pre_cadastro/index.php?sucesso=1&codigo=' . $codigo);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao criar pré-cadastro: " . $e->getMessage();
            error_log("Erro ao criar pré-cadastro: " . $e->getMessage());
        }
    }
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
    <title>Criar Pré-cadastro - Financeiro</title>
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
                            Criar Pré-cadastro
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Financeiro</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/pre_cadastro/index.php'); ?>">Pré-cadastros</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Criar</li>
                            </ul>
                        </nav>
                    </div>

                    <div class="row">
                        <div class="col-md-8 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-account-plus text-primary me-2"></i>
                                        Novo Pré-cadastro de Aluno
                                    </h4>
                                    <p class="text-muted">Preencha os dados básicos do aluno. Um link será gerado para que os responsáveis preencham as informações completas.</p>
                                    
                                    <?php if (isset($erro)): ?>
                                    <div class="alert alert-danger">
                                        <i class="mdi mdi-alert-circle"></i>
                                        <?php echo htmlspecialchars($erro); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="criar.php">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="nome" class="form-label">Nome do Aluno *</label>
                                                    <input type="text" class="form-control" id="nome" name="nome" 
                                                           value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" 
                                                           required placeholder="Digite o nome completo do aluno">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="turma_id" class="form-label">Turma Sugerida</label>
                                                    <select class="form-control" id="turma_id" name="turma_id">
                                                        <option value="">Selecione uma turma</option>
                                                        <?php foreach ($turmas as $turma): ?>
                                                        <option value="<?php echo $turma['id']; ?>" 
                                                                <?php echo (isset($_POST['turma_id']) && $_POST['turma_id'] == $turma['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($turma['nome']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="observacoes" class="form-label">Observações</label>
                                                    <textarea class="form-control" id="observacoes" name="observacoes" 
                                                              rows="4" placeholder="Informações adicionais sobre o aluno, responsável, etc..."><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-gradient-primary me-2">
                                                    <i class="mdi mdi-content-save"></i> Criar Pré-cadastro
                                                </button>
                                                <a href="<?php echo getPageUrl('financeiro/pre_cadastro/index.php'); ?>" class="btn btn-light">
                                                    <i class="mdi mdi-arrow-left"></i> Voltar
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-information text-info me-2"></i>
                                        Como Funciona
                                    </h4>
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-primary"></div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">1. Criação</h6>
                                                <p class="timeline-description">Você cria um pré-cadastro com dados básicos do aluno.</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-warning"></div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">2. Link Gerado</h6>
                                                <p class="timeline-description">Sistema gera um link único e seguro para o responsável.</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-info"></div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">3. Preenchimento</h6>
                                                <p class="timeline-description">Responsável acessa o link e preenche dados completos.</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">4. Aprovação</h6>
                                                <p class="timeline-description">Secretaria revisa e aprova o cadastro completo.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
</body>
</html>
