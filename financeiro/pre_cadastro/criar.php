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
    $stmt = $pdo->prepare("SELECT id, nome, ano_letivo FROM turmas ORDER BY nome");
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
                                                    
                                                    <!-- Campo de busca melhorado -->
                                                    <div class="search-container mb-3">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text bg-primary text-white">
                                                                    <i class="mdi mdi-magnify"></i>
                                                                </span>
                                                            </div>
                                                            <input type="text" class="form-control" id="buscar-aluno" 
                                                                   placeholder="Digite o nome do aluno para pesquisar..." 
                                                                   autocomplete="off">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-secondary" type="button" id="limpar-busca">
                                                                    <i class="mdi mdi-close"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted mt-1 d-block">
                                                            <i class="mdi mdi-information"></i> Digite para filtrar a lista de alunos em tempo real
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- Lista de resultados melhorada -->
                                                    <div class="alunos-container">
                                                        <div class="card">
                                                            <div class="card-header bg-light">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0">
                                                                        <i class="mdi mdi-account-multiple text-primary"></i>
                                                                        Lista de Alunos
                                                                    </h6>
                                                                    <span class="badge badge-primary" id="contador-alunos">67 alunos</span>
                                                                </div>
                                                            </div>
                                                            <div class="card-body p-0">
                                                                <div class="alunos-list" id="alunos-list" style="max-height: 300px; overflow-y: auto;">
                                                                    <!-- Alunos serão carregados aqui via JavaScript -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Campo hidden para o valor selecionado -->
                                                    <input type="hidden" id="aluno_existente_id" name="aluno_existente_id" value="">
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
                                                            <?php if ($turma['ano_letivo']): ?>
                                                                (<?php echo htmlspecialchars($turma['ano_letivo']); ?>)
                                                            <?php endif; ?>
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
    
    <style>
        .aluno-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .aluno-item:hover {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        
        .aluno-item.selected {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .aluno-info {
            flex: 1;
        }
        
        .aluno-nome {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }
        
        .aluno-turma {
            font-size: 0.85em;
            color: #666;
        }
        
        .aluno-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
        }
        
        .no-results {
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }
        
        .no-results i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .search-container .input-group-text {
            border-color: #007bff;
        }
        
        .search-container .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoNovo = document.getElementById('tipo_novo');
            const tipoExistente = document.getElementById('tipo_existente');
            const campoAlunoExistente = document.getElementById('campo-aluno-existente');
            const campoNomeNovo = document.getElementById('campo-nome-novo');
            const nomeField = document.getElementById('nome');
            const alunoHidden = document.getElementById('aluno_existente_id');
            const buscarAluno = document.getElementById('buscar-aluno');
            const limparBusca = document.getElementById('limpar-busca');
            const contadorAlunos = document.getElementById('contador-alunos');
            const alunosList = document.getElementById('alunos-list');
            
            // Dados dos alunos (carregados do PHP)
            const alunos = <?php
                try {
                    $stmt = $pdo->query("
                        SELECT a.id, a.nome, a.turma_id, t.nome as turma_nome, t.ano_letivo
                        FROM alunos a 
                        LEFT JOIN turmas t ON a.turma_id = t.id 
                        ORDER BY a.nome
                    ");
                    $alunos = [];
                    while ($aluno = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $alunos[] = [
                            'id' => (int)$aluno['id'],
                            'nome' => $aluno['nome'],
                            'turma_nome' => $aluno['turma_nome'],
                            'ano_letivo' => $aluno['ano_letivo']
                        ];
                    }
                    echo json_encode($alunos, JSON_UNESCAPED_UNICODE);
                } catch (PDOException $e) {
                    echo '[]';
                }
            ?>;
            
            console.log('Alunos carregados:', alunos);
            
            let alunosFiltrados = [...alunos];
            let alunoSelecionado = null;
            
            function toggleCampos() {
                if (tipoExistente.checked) {
                    campoAlunoExistente.style.display = 'block';
                    campoNomeNovo.style.display = 'none';
                    nomeField.required = false;
                    alunoHidden.required = true;
                    carregarListaAlunos();
                } else {
                    campoAlunoExistente.style.display = 'none';
                    campoNomeNovo.style.display = 'block';
                    nomeField.required = true;
                    alunoHidden.required = false;
                }
            }
            
            function carregarListaAlunos() {
                if (!alunosList) {
                    console.error('Elemento alunos-list não encontrado');
                    return;
                }
                
                alunosList.innerHTML = '';
                
                if (alunosFiltrados.length === 0) {
                    alunosList.innerHTML = `
                        <div class="no-results">
                            <i class="mdi mdi-account-search"></i>
                            <p>Nenhum aluno encontrado</p>
                            <small>Tente ajustar os termos de busca</small>
                        </div>
                    `;
                    return;
                }
                
                alunosFiltrados.forEach(aluno => {
                    const alunoItem = document.createElement('div');
                    alunoItem.className = 'aluno-item';
                    alunoItem.dataset.alunoId = aluno.id;
                    
                    const turmaInfo = aluno.turma_nome ? 
                        `${aluno.turma_nome}${aluno.ano_letivo ? ` (${aluno.ano_letivo})` : ''}` : 
                        'Sem turma';
                    
                    alunoItem.innerHTML = `
                        <div class="aluno-info">
                            <div class="aluno-nome">${aluno.nome}</div>
                            <div class="aluno-turma">
                                <i class="mdi mdi-school"></i> ${turmaInfo}
                            </div>
                        </div>
                        <div class="aluno-badge">ID: ${aluno.id}</div>
                    `;
                    
                    alunoItem.addEventListener('click', () => selecionarAluno(aluno));
                    alunosList.appendChild(alunoItem);
                });
                
                if (contadorAlunos) {
                    contadorAlunos.textContent = `${alunosFiltrados.length} de ${alunos.length} alunos`;
                }
            }
            
            function selecionarAluno(aluno) {
                // Remover seleção anterior
                document.querySelectorAll('.aluno-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                // Selecionar novo aluno
                const alunoItem = document.querySelector(`[data-aluno-id="${aluno.id}"]`);
                if (alunoItem) {
                    alunoItem.classList.add('selected');
                }
                
                alunoSelecionado = aluno;
                alunoHidden.value = aluno.id;
                nomeField.value = aluno.nome;
                
                // Destacar o campo de busca
                buscarAluno.value = aluno.nome;
                buscarAluno.style.backgroundColor = '#e8f5e8';
                setTimeout(() => {
                    buscarAluno.style.backgroundColor = '';
                }, 1000);
            }
            
            function filtrarAlunos(termo) {
                const termoLower = termo.toLowerCase();
                
                alunosFiltrados = alunos.filter(aluno => {
                    const nomeMatch = aluno.nome.toLowerCase().includes(termoLower);
                    const turmaMatch = aluno.turma_nome && aluno.turma_nome.toLowerCase().includes(termoLower);
                    const anoMatch = aluno.ano_letivo && aluno.ano_letivo.toString().includes(termoLower);
                    
                    return nomeMatch || turmaMatch || anoMatch;
                });
                
                carregarListaAlunos();
            }
            
            // Event listeners
            if (tipoNovo) tipoNovo.addEventListener('change', toggleCampos);
            if (tipoExistente) tipoExistente.addEventListener('change', toggleCampos);
            
            if (buscarAluno) {
                buscarAluno.addEventListener('input', function() {
                    filtrarAlunos(this.value);
                });
            }
            
            if (limparBusca) {
                limparBusca.addEventListener('click', function() {
                    buscarAluno.value = '';
                    filtrarAlunos('');
                    alunoSelecionado = null;
                    alunoHidden.value = '';
                    nomeField.value = '';
                });
            }
            
            // Inicializar
            toggleCampos();
        });
    </script>
</body>
</html>
