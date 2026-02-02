<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

$pdo = getConnection();

// Buscar todos os alunos cadastrados para o seletor
try {
    $stmt = $pdo->query("
        SELECT a.id, a.nome, a.nome_completo, a.cpf, a.status_cadastro,
               t.nome as turma_nome, t.ano_letivo
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.status_cadastro IN ('completo', 'aprovado')
        ORDER BY a.nome
    ");
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alunos = [];
}

// Buscar dados do aluno selecionado (via AJAX)
$aluno_selecionado = null;
if (isset($_GET['aluno_id']) && !empty($_GET['aluno_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.id, a.nome, a.nome_completo, a.cpf, a.rg, a.data_nascimento, a.sexo,
                a.nome_resp_legal, a.cpf_resp_legal, a.rg as rg_resp_legal,
                a.profissao_resp_legal, a.grau_parentesco_resp_legal,
                a.endereco, a.numero, a.complemento, a.bairro, a.cidade, a.estado, a.cep,
                a.telefone1, a.telefone2, a.email,
                a.status_cadastro, t.nome as turma_nome, t.ano_letivo,
                pc.turma_futura_id,
                tf.nome as turma_futura_nome, tf.ano_letivo as turma_futura_ano_letivo
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
            LEFT JOIN turmas tf ON pc.turma_futura_id = tf.id
            WHERE a.id = ?
        ");
        $stmt->execute([$_GET['aluno_id']]);
        $aluno_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar aluno: " . $e->getMessage());
    }
}

// Gerar PDF se solicitado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_contrato'])) {
    $aluno_id = (int)$_POST['aluno_id'];
    
    if ($aluno_id) {
        // Redirecionar para a página de geração do contrato
        header('Location: gerar_contrato.php?aluno_id=' . $aluno_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Contrato - Financeiro</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
    <style>
        .resumo-box {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .info-line {
            border-bottom: 1px dashed #ddd;
            padding: 8px 0;
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            color: #333;
            display: inline-block;
            min-width: 120px;
        }
        .info-value {
            color: #666;
        }
        .alert-info-resumo {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
    </style>
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
                                <i class="mdi mdi-file-document"></i>
                            </span>
                            Gerar Contrato de Matrícula
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Financeiro</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/contratos/selecionar_aluno.php'); ?>">Contratos</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Gerar</li>
                            </ul>
                        </nav>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-file-document-box text-primary me-2"></i>
                                        Selecionar Aluno para Gerar Contrato
                                    </h4>
                                    <p class="text-muted">Selecione um aluno cadastrado para gerar o contrato de matrícula em PDF.</p>
                                    
                                    <form method="GET" action="selecionar_aluno.php" id="form-selecionar">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="aluno_id" class="form-label">
                                                        <i class="mdi mdi-account-search"></i> Buscar Aluno
                                                    </label>
                                                    <select class="form-control" id="aluno_id" name="aluno_id" required onchange="this.form.submit()">
                                                        <option value="">-- Selecione um aluno --</option>
                                                        <?php foreach ($alunos as $aluno): ?>
                                                        <option value="<?php echo $aluno['id']; ?>" 
                                                                <?php echo (isset($_GET['aluno_id']) && $_GET['aluno_id'] == $aluno['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($aluno['nome']); ?>
                                                            <?php if ($aluno['cpf']): ?>
                                                                - CPF: <?php echo htmlspecialchars($aluno['cpf']); ?>
                                                            <?php endif; ?>
                                                            <?php if ($aluno['turma_nome']): ?>
                                                                - <?php echo htmlspecialchars($aluno['turma_nome']); ?>
                                                                <?php if ($aluno['ano_letivo']): ?>
                                                                    (<?php echo htmlspecialchars($aluno['ano_letivo']); ?>)
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        <i class="mdi mdi-information"></i> Apenas alunos com cadastro completo podem ter contrato gerado.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($aluno_selecionado): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-file-check text-success me-2"></i>
                                        Resumo dos Dados para o Contrato
                                    </h4>
                                    
                                    <div class="alert alert-info-resumo">
                                        <i class="mdi mdi-information"></i>
                                        <strong>Verifique os dados abaixo antes de gerar o contrato.</strong>
                                    </div>
                                    
                                    <!-- Dados do Responsável Legal -->
                                    <div class="resumo-box mb-4">
                                        <h5 class="mb-3">
                                            <i class="mdi mdi-account-star text-primary"></i>
                                            Dados do Responsável Legal
                                        </h5>
                                        
                                        <div class="info-line">
                                            <span class="info-label"><i class="mdi mdi-account"></i> Nome:</span>
                                            <span class="info-value">
                                                <?php echo htmlspecialchars($aluno_selecionado['nome_resp_legal'] ?? 'Não informado'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-credit-card"></i> CPF:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['cpf_resp_legal'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-briefcase"></i> Profissão:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['profissao_resp_legal'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-card-account-details"></i> RG:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['rg_resp_legal'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-account-heart"></i> Parentesco:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['grau_parentesco_resp_legal'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-phone"></i> Telefone:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['telefone1'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-email"></i> E-mail:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['email'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-line">
                                            <span class="info-label"><i class="mdi mdi-map-marker"></i> Endereço:</span>
                                            <span class="info-value">
                                                <?php 
                                                $endereco_completo = trim(
                                                    ($aluno_selecionado['endereco'] ?? '') . 
                                                    ', ' . ($aluno_selecionado['numero'] ?? '') .
                                                    (!empty($aluno_selecionado['complemento']) ? ' - ' . $aluno_selecionado['complemento'] : '')
                                                );
                                                echo htmlspecialchars($endereco_completo ?: 'Não informado');
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-city"></i> Bairro:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['bairro'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-city-variant"></i> Cidade:</span>
                                                    <span class="info-value">
                                                        <?php 
                                                        $cidade_uf = trim(
                                                            ($aluno_selecionado['cidade'] ?? '') . 
                                                            (!empty($aluno_selecionado['estado']) ? '/' . $aluno_selecionado['estado'] : '')
                                                        );
                                                        echo htmlspecialchars($cidade_uf ?: 'Não informado');
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Dados do Aluno -->
                                    <div class="resumo-box mb-4">
                                        <h5 class="mb-3">
                                            <i class="mdi mdi-account-school text-success"></i>
                                            Dados do Aluno
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-account"></i> Nome:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['nome_completo'] ?: $aluno_selecionado['nome']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-credit-card"></i> CPF:</span>
                                                    <span class="info-value">
                                                        <?php echo htmlspecialchars($aluno_selecionado['cpf'] ?? 'Não informado'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-school"></i> Turma:</span>
                                                    <span class="info-value">
                                                        <?php 
                                                        // Priorizar turma futura se existir no pré-cadastro
                                                        if (!empty($aluno_selecionado['turma_futura_nome'])) {
                                                            $turma_completa = trim(
                                                                $aluno_selecionado['turma_futura_nome'] .
                                                                ($aluno_selecionado['turma_futura_ano_letivo'] ? ' (' . $aluno_selecionado['turma_futura_ano_letivo'] . ')' : '')
                                                            );
                                                        } else {
                                                            $turma_completa = trim(
                                                                ($aluno_selecionado['turma_nome'] ?? 'Não definida') .
                                                                ($aluno_selecionado['ano_letivo'] ? ' (' . $aluno_selecionado['ano_letivo'] . ')' : '')
                                                            );
                                                        }
                                                        echo htmlspecialchars($turma_completa);
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-calendar"></i> Data Nascimento:</span>
                                                    <span class="info-value">
                                                        <?php 
                                                        echo $aluno_selecionado['data_nascimento'] ? 
                                                            date('d/m/Y', strtotime($aluno_selecionado['data_nascimento'])) : 
                                                            'Não informado';
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-line">
                                                    <span class="info-label"><i class="mdi mdi-gender"></i> Sexo:</span>
                                                    <span class="info-value">
                                                        <?php 
                                                        $sexo = $aluno_selecionado['sexo'] ?? '';
                                                        echo $sexo === 'M' ? 'Masculino' : ($sexo === 'F' ? 'Feminino' : 'Não informado');
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Botão para gerar contrato -->
                                    <div class="row mt-4">
                                        <div class="col-md-12 text-center">
                                            <form method="POST" action="selecionar_aluno.php">
                                                <input type="hidden" name="aluno_id" value="<?php echo $aluno_selecionado['id']; ?>">
                                                <button type="submit" name="gerar_contrato" class="btn btn-gradient-success btn-lg">
                                                    <i class="mdi mdi-file-pdf"></i> Gerar Contrato em PDF
                                                </button>
                                                <a href="<?php echo getPageUrl('financeiro/contratos/selecionar_aluno.php'); ?>" class="btn btn-light btn-lg">
                                                    <i class="mdi mdi-arrow-left"></i> Cancelar
                                                </a>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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

