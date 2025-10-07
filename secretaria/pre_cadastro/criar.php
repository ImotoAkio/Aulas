<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
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
            redirectTo('secretaria/pre_cadastro/index.php?sucesso=1&codigo=' . $codigo);
            
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
    <title>Criar Pré-cadastro - Secretaria</title>
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
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/pre_cadastro/index.php'); ?>">Pré-cadastros</a></li>
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
                                            <div class="col-md-12 mb-4">
                                                <div class="form-group">
                                                    <label class="form-label">Tipo de Cadastro *</label>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="tipo_cadastro" id="tipo_novo" value="novo" 
                                                                       <?php echo ($_POST['tipo_cadastro'] ?? 'novo') === 'novo' ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="tipo_novo">
                                                                    <i class="mdi mdi-account-plus text-success me-2"></i>
                                                                    <strong>Novo Aluno</strong>
                                                                    <small class="d-block text-muted">Primeira matrícula no colégio</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="tipo_cadastro" id="tipo_existente" value="existente" 
                                                                       <?php echo ($_POST['tipo_cadastro'] ?? '') === 'existente' ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="tipo_existente">
                                                                    <i class="mdi mdi-account-sync text-warning me-2"></i>
                                                                    <strong>Re-matrícula</strong>
                                                                    <small class="d-block text-muted">Aluno já matriculado anteriormente</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row" id="campo-aluno-existente" style="display: none;">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="aluno_existente_id" class="form-label">Selecionar Aluno Existente</label>
                                                    <select class="form-control" id="aluno_existente_id" name="aluno_existente_id">
                                                        <option value="">Selecione o aluno...</option>
                                                        <?php
                                                        // Buscar alunos já matriculados (que já passaram pelo processo de aprovação)
                                                        try {
                                                            $stmt = $pdo->query("
                                                                SELECT id, nome, turma_id, t.nome as turma_nome 
                                                                FROM alunos a 
                                                                LEFT JOIN turmas t ON a.turma_id = t.id 
                                                                WHERE status_cadastro = 'completo' 
                                                                ORDER BY nome
                                                            ");
                                                            while ($aluno = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                                $selected = ($_POST['aluno_existente_id'] ?? '') == $aluno['id'] ? 'selected' : '';
                                                                echo "<option value=\"{$aluno['id']}\" {$selected}>";
                                                                echo htmlspecialchars($aluno['nome']);
                                                                if ($aluno['turma_nome']) {
                                                                    echo " - Turma: " . htmlspecialchars($aluno['turma_nome']);
                                                                }
                                                                echo "</option>";
                                                            }
                                                        } catch (PDOException $e) {
                                                            error_log("Erro ao buscar alunos: " . $e->getMessage());
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row" id="campo-nome-novo" style="display: block;">
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
                                                <a href="<?php echo getPageUrl('secretaria/pre_cadastro/index.php'); ?>" class="btn btn-light">
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
                                                <p class="timeline-description">Você revisa e aprova o cadastro completo.</p>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoNovo = document.getElementById('tipo_novo');
            const tipoExistente = document.getElementById('tipo_existente');
            const campoAlunoExistente = document.getElementById('campo-aluno-existente');
            const campoNomeNovo = document.getElementById('campo-nome-novo');
            const nomeField = document.getElementById('nome');
            const alunoSelect = document.getElementById('aluno_existente_id');
            
            function toggleCampos() {
                if (tipoExistente.checked) {
                    // Re-matrícula: mostrar seleção de aluno, ocultar campo nome
                    campoAlunoExistente.style.display = 'block';
                    campoNomeNovo.style.display = 'none';
                    nomeField.required = false;
                    alunoSelect.required = true;
                } else {
                    // Novo aluno: ocultar seleção de aluno, mostrar campo nome
                    campoAlunoExistente.style.display = 'none';
                    campoNomeNovo.style.display = 'block';
                    nomeField.required = true;
                    alunoSelect.required = false;
                }
            }
            
            // Event listeners
            tipoNovo.addEventListener('change', toggleCampos);
            tipoExistente.addEventListener('change', toggleCampos);
            
            // Preencher nome automaticamente quando selecionar aluno existente
            alunoSelect.addEventListener('change', function() {
                if (this.value) {
                    const selectedOption = this.options[this.selectedIndex];
                    const nomeAluno = selectedOption.text.split(' - Turma:')[0];
                    nomeField.value = nomeAluno;
                }
            });
            
            // Inicializar estado
            toggleCampos();
        });
    </script>
</body>
</html>
