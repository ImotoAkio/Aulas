<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

// Verificar se o ID foi fornecido
$aluno_id = (int)($_GET['id'] ?? 0);
if ($aluno_id <= 0) {
    redirectTo('secretaria/cad/listar_alunos.php');
}

// Buscar dados do aluno
try {
    error_log("DEBUG: Buscando aluno ID: " . $aluno_id);
    
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome, t.ano_letivo
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("DEBUG: Aluno encontrado: " . ($aluno ? 'SIM' : 'NÃO'));
    if ($aluno) {
        error_log("DEBUG: Nome do aluno: " . $aluno['nome']);
    }
    
    if (!$aluno) {
        error_log("DEBUG: Redirecionando - aluno não encontrado");
        redirectTo('secretaria/cad/listar_alunos.php');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aluno: " . $e->getMessage());
    redirectTo('secretaria/cad/listar_alunos.php');
}

// Função para formatar data
function formatarData($data) {
    if (!$data || $data === '0000-00-00') return 'Não informado';
    return date('d/m/Y', strtotime($data));
}

// Função para formatar CPF
function formatarCPF($cpf) {
    if (!$cpf) return 'Não informado';
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Função para formatar telefone
function formatarTelefone($telefone) {
    if (!$telefone) return 'Não informado';
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
    } elseif (strlen($telefone) === 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }
    return $telefone;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Aluno - <?php echo htmlspecialchars($aluno['nome']); ?></title>
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
                                <i class="mdi mdi-account"></i>
                            </span>
                            Visualizar Aluno
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/cad/listar_alunos.php'); ?>">Cadastros</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($aluno['nome']); ?></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Botões de ação -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <a href="<?php echo getPageUrl('secretaria/cad/listar_alunos.php'); ?>" class="btn btn-gradient-secondary">
                                <i class="mdi mdi-arrow-left"></i> Voltar
                            </a>
                            <a href="<?php echo getPageUrl('secretaria/cad/editar_aluno.php?id=' . $aluno_id); ?>" class="btn btn-gradient-primary">
                                <i class="mdi mdi-pencil"></i> Editar
                            </a>
                        </div>
                    </div>

                    <!-- Informações do Aluno -->
                    <div class="row">
                        <!-- Dados Pessoais -->
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-account text-primary me-2"></i>
                                        Dados Pessoais
                                    </h4>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>Nome:</strong><br><?php echo htmlspecialchars($aluno['nome']); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Data de Nascimento:</strong><br><?php echo formatarData($aluno['data_nascimento']); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>CPF:</strong><br><?php echo formatarCPF($aluno['cpf']); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>RG:</strong><br><?php echo htmlspecialchars($aluno['rg'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Naturalidade:</strong><br><?php echo htmlspecialchars($aluno['naturalidade'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Estado:</strong><br><?php echo htmlspecialchars($aluno['naturalidade_estado'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>NIS:</strong><br><?php echo htmlspecialchars($aluno['nis'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Tipo Sanguíneo:</strong><br><?php echo htmlspecialchars($aluno['tipo_sanguineo'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Fator RH:</strong><br><?php echo htmlspecialchars($aluno['fator_rh'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Sexo:</strong><br><?php echo htmlspecialchars($aluno['sexo'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dados Escolares -->
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-school text-success me-2"></i>
                                        Dados Escolares
                                    </h4>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>Turma:</strong><br><?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não definida'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Ano Letivo:</strong><br><?php echo htmlspecialchars($aluno['ano_letivo'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Status:</strong><br>
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
                                                        $status_text = ucfirst($aluno['status_cadastro']);
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Código Pré-cadastro:</strong><br><?php echo htmlspecialchars($aluno['codigo_pre_cadastro'] ?? 'Não gerado'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informações dos Pais -->
                    <div class="row">
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-account-multiple text-info me-2"></i>
                                        Informações dos Pais
                                    </h4>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>Nome da Mãe:</strong><br><?php echo htmlspecialchars($aluno['nome_mae'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>CPF da Mãe:</strong><br><?php echo formatarCPF($aluno['cpf_mae']); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Nome do Pai:</strong><br><?php echo htmlspecialchars($aluno['nome_pai'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>CPF do Pai:</strong><br><?php echo formatarCPF($aluno['cpf_pai']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações do Responsável -->
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-account-star text-warning me-2"></i>
                                        Responsável Legal
                                    </h4>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>Nome:</strong><br><?php echo htmlspecialchars($aluno['nome_responsavel'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>CPF:</strong><br><?php echo formatarCPF($aluno['cpf_responsavel'] ?? ''); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Telefone:</strong><br><?php echo formatarTelefone($aluno['telefone_responsavel'] ?? ''); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Email:</strong><br><?php echo htmlspecialchars($aluno['email_responsavel'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Profissão:</strong><br><?php echo htmlspecialchars($aluno['profissao_responsavel'] ?? 'Não informado'); ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Local de Trabalho:</strong><br><?php echo htmlspecialchars($aluno['local_trabalho_responsavel'] ?? 'Não informado'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informações Médicas -->
                    <div class="row">
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-medical-bag text-danger me-2"></i>
                                        Informações Médicas
                                    </h4>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <p><strong>Observações Médicas:</strong><br><?php echo htmlspecialchars($aluno['observacoes_medicas'] ?? 'Nenhuma observação médica registrada'); ?></p>
                                        </div>
                                        <div class="col-sm-12">
                                            <p><strong>Medicamentos:</strong><br><?php echo htmlspecialchars($aluno['medicamentos'] ?? 'Nenhum medicamento registrado'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações Adicionais -->
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-information text-secondary me-2"></i>
                                        Informações Adicionais
                                    </h4>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>Preenchido por Responsável:</strong><br>
                                                <?php if ($aluno['preenchido_por_responsavel']): ?>
                                                    <span class="badge badge-success">Sim</span>
                                                    <?php if ($aluno['dados_preenchidos_em']): ?>
                                                        <br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($aluno['dados_preenchidos_em'])); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Não</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Observações:</strong><br><?php echo htmlspecialchars($aluno['observacoes'] ?? 'Nenhuma observação registrada'); ?></p>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
</body>
</html>
