<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

$aluno_id = (int)($_GET['id'] ?? 0);

if (!$aluno_id) {
    redirectTo('secretaria/pre_cadastro/index.php');
}

// Buscar dados do aluno
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome, pc.criado_em, pc.link_expiracao, pc.observacoes,
               u.nome as criado_por_nome
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        LEFT JOIN usuarios u ON pc.criado_por = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aluno) {
        redirectTo('secretaria/pre_cadastro/index.php');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aluno: " . $e->getMessage());
    redirectTo('secretaria/pre_cadastro/index.php');
}

// Processar aprovação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'aprovar' && $aluno['status_cadastro'] === 'completo') {
        try {
            $stmt = $pdo->prepare("UPDATE alunos SET status_cadastro = 'aprovado' WHERE id = ?");
            $stmt->execute([$aluno_id]);
            
            $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET status = 'aprovado' WHERE aluno_id = ?");
            $stmt->execute([$aluno_id]);
            
            $sucesso = "Pré-cadastro aprovado com sucesso!";
            $aluno['status_cadastro'] = 'aprovado';
            
        } catch (PDOException $e) {
            $erro = "Erro ao aprovar pré-cadastro: " . $e->getMessage();
            error_log("Erro ao aprovar pré-cadastro: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Pré-cadastro - Secretaria</title>
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
                                <i class="mdi mdi-eye"></i>
                            </span>
                            Visualizar Pré-cadastro
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/pre_cadastro/index.php'); ?>">Pré-cadastros</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Visualizar</li>
                            </ul>
                        </nav>
                    </div>

                    <?php if (isset($sucesso)): ?>
                    <div class="alert alert-success">
                        <i class="mdi mdi-check-circle"></i>
                        <?php echo htmlspecialchars($sucesso); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($erro)): ?>
                    <div class="alert alert-danger">
                        <i class="mdi mdi-alert-circle"></i>
                        <?php echo htmlspecialchars($erro); ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title">
                                            <i class="mdi mdi-account text-primary me-2"></i>
                                            Dados do Aluno
                                        </h4>
                                        <div>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($aluno['status_cadastro']) {
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
                                            <span class="badge <?php echo $status_class; ?> fs-6"><?php echo $status_text; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($aluno['nome']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Turma:</strong> <?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não definida'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($aluno['cpf']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>RG:</strong> <?php echo htmlspecialchars($aluno['rg'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['data_nascimento']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Data de Nascimento:</strong> <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Sexo:</strong> <?php echo $aluno['sexo'] === 'M' ? 'Masculino' : ($aluno['sexo'] === 'F' ? 'Feminino' : 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['endereco']): ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p><strong>Endereço:</strong> 
                                                <?php echo htmlspecialchars($aluno['endereco']); ?>
                                                <?php if ($aluno['numero']): ?>, <?php echo htmlspecialchars($aluno['numero']); ?><?php endif; ?>
                                                <?php if ($aluno['complemento']): ?>, <?php echo htmlspecialchars($aluno['complemento']); ?><?php endif; ?>
                                                <?php if ($aluno['bairro']): ?>, <?php echo htmlspecialchars($aluno['bairro']); ?><?php endif; ?>
                                                <?php if ($aluno['cidade']): ?>, <?php echo htmlspecialchars($aluno['cidade']); ?><?php endif; ?>
                                                <?php if ($aluno['estado']): ?> - <?php echo htmlspecialchars($aluno['estado']); ?><?php endif; ?>
                                                <?php if ($aluno['cep']): ?>, CEP: <?php echo htmlspecialchars($aluno['cep']); ?><?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['telefone1'] || $aluno['telefone2']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?php if ($aluno['telefone1']): ?>
                                            <p><strong>Telefone Principal:</strong> <?php echo htmlspecialchars($aluno['telefone1']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if ($aluno['telefone2']): ?>
                                            <p><strong>Telefone Secundário:</strong> <?php echo htmlspecialchars($aluno['telefone2']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['nome_resp_legal']): ?>
                                    <hr>
                                    <h5 class="mb-3">Dados do Responsável</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($aluno['nome_resp_legal']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Parentesco:</strong> <?php echo htmlspecialchars($aluno['grau_parentesco_resp_legal'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($aluno['cpf_resp_legal']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf_resp_legal']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Profissão:</strong> <?php echo htmlspecialchars($aluno['profissao_resp_legal'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['local_trabalho_resp_legal']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Local de Trabalho:</strong> <?php echo htmlspecialchars($aluno['local_trabalho_resp_legal']); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['nome_mae'] || $aluno['nome_pai']): ?>
                                    <hr>
                                    <h5 class="mb-3">Dados dos Pais</h5>
                                    <?php if ($aluno['nome_mae']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Nome da Mãe:</strong> <?php echo htmlspecialchars($aluno['nome_mae']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>CPF da Mãe:</strong> <?php echo htmlspecialchars($aluno['cpf_mae'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['nome_pai']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Nome do Pai:</strong> <?php echo htmlspecialchars($aluno['nome_pai']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>CPF do Pai:</strong> <?php echo htmlspecialchars($aluno['cpf_pai'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['nis'] || $aluno['tipo_sanguineo'] || $aluno['fator_rh']): ?>
                                    <hr>
                                    <h5 class="mb-3">Informações Adicionais</h5>
                                    <div class="row">
                                        <?php if ($aluno['nis']): ?>
                                        <div class="col-md-4">
                                            <p><strong>NIS:</strong> <?php echo htmlspecialchars($aluno['nis']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($aluno['tipo_sanguineo']): ?>
                                        <div class="col-md-4">
                                            <p><strong>Tipo Sanguíneo:</strong> <?php echo htmlspecialchars($aluno['tipo_sanguineo']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($aluno['fator_rh']): ?>
                                        <div class="col-md-4">
                                            <p><strong>Fator RH:</strong> <?php echo htmlspecialchars($aluno['fator_rh']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['alergias']): ?>
                                    <hr>
                                    <h5 class="mb-3">Informações Médicas</h5>
                                    <?php if ($aluno['alergias']): ?>
                                    <p><strong>Alergias:</strong> <?php echo htmlspecialchars($aluno['alergias']); ?></p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-information text-info me-2"></i>
                                        Informações do Processo
                                    </h4>
                                    
                                    <div class="mb-3">
                                        <strong>Criado por:</strong><br>
                                        <?php echo htmlspecialchars($aluno['criado_por_nome'] ?? 'N/A'); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Data de Criação:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($aluno['criado_em'])); ?>
                                    </div>
                                    
                                    <?php if ($aluno['preenchido_por_responsavel']): ?>
                                    <div class="mb-3">
                                        <strong>Preenchido em:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($aluno['dados_preenchidos_em'])); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['codigo_pre_cadastro']): ?>
                                    <div class="mb-3">
                                        <strong>Código do Link:</strong><br>
                                        <code><?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?></code>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Link de Acesso:</strong><br>
                                        <small class="text-muted"><?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=<?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['link_expiracao']): ?>
                                    <div class="mb-3">
                                        <strong>Expira em:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($aluno['link_expiracao'])); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($aluno['observacoes']): ?>
                                    <div class="mb-3">
                                        <strong>Observações:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($aluno['observacoes'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($aluno['status_cadastro'] === 'completo'): ?>
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-check-circle text-success me-2"></i>
                                        Aprovar Cadastro
                                    </h4>
                                    <p class="text-muted">Este cadastro está completo e pronto para aprovação.</p>
                                    
                                    <form method="POST" action="visualizar.php?id=<?php echo $aluno_id; ?>">
                                        <input type="hidden" name="acao" value="aprovar">
                                        <button type="submit" class="btn btn-gradient-success w-100">
                                            <i class="mdi mdi-check"></i> Aprovar Cadastro
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="<?php echo getPageUrl('secretaria/pre_cadastro/index.php'); ?>" class="btn btn-light">
                                <i class="mdi mdi-arrow-left"></i> Voltar para Lista
                            </a>
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
