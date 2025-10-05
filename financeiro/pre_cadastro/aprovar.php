<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

$pdo = getConnection();

$aluno_id = (int)($_GET['id'] ?? 0);

if (!$aluno_id) {
    redirectTo('financeiro/pre_cadastro/index.php');
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
        WHERE a.id = ? AND a.status_cadastro = 'completo'
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aluno) {
        redirectTo('financeiro/pre_cadastro/index.php');
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aluno: " . $e->getMessage());
    redirectTo('financeiro/pre_cadastro/index.php');
}

// Processar aprovação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'aprovar') {
            try {
                $pdo->beginTransaction();
                
                // Atualizar status do aluno para aprovado
                $stmt = $pdo->prepare("UPDATE alunos SET status_cadastro = 'aprovado' WHERE id = ?");
                $stmt->execute([$aluno_id]);
                
                // Atualizar status no controle
                $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET status = 'aprovado' WHERE aluno_id = ?");
                $stmt->execute([$aluno_id]);
                
                $pdo->commit();
                
                $sucesso = "Pré-cadastro aprovado com sucesso! O aluno está oficialmente matriculado.";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $erro = "Erro ao aprovar pré-cadastro: " . $e->getMessage();
                error_log("Erro ao aprovar pré-cadastro: " . $e->getMessage());
            }
        } elseif ($_POST['acao'] === 'rejeitar') {
            $motivo_rejeicao = trim($_POST['motivo_rejeicao'] ?? '');
            
            if (empty($motivo_rejeicao)) {
                $erro = "Motivo da rejeição é obrigatório.";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Atualizar status do aluno para pendente (volta para preenchimento)
                    $stmt = $pdo->prepare("UPDATE alunos SET status_cadastro = 'pre_cadastro' WHERE id = ?");
                    $stmt->execute([$aluno_id]);
                    
                    // Atualizar observações com motivo da rejeição
                    $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET observacoes = CONCAT(IFNULL(observacoes, ''), '\n\nREJEITADO: ', ?) WHERE aluno_id = ?");
                    $stmt->execute([$motivo_rejeicao, $aluno_id]);
                    
                    $pdo->commit();
                    
                    $sucesso = "Pré-cadastro rejeitado. O responsável pode corrigir os dados e enviar novamente.";
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $erro = "Erro ao rejeitar pré-cadastro: " . $e->getMessage();
                    error_log("Erro ao rejeitar pré-cadastro: " . $e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Pré-cadastro - Financeiro</title>
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
                            <span class="page-title-icon bg-gradient-success text-white me-2">
                                <i class="mdi mdi-check-circle"></i>
                            </span>
                            Aprovar Pré-cadastro
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Financeiro</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/pre_cadastro/index.php'); ?>">Pré-cadastros</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Aprovar</li>
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
                                            Dados do Aluno - <?php echo htmlspecialchars($aluno['nome']); ?>
                                        </h4>
                                        <div>
                                            <span class="badge badge-info fs-6">Completo - Aguardando Aprovação</span>
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
                                    
                                    <?php if ($aluno['alergias']): ?>
                                    <hr>
                                    <h5 class="mb-3">Informações Médicas</h5>
                                    <p><strong>Alergias:</strong> <?php echo htmlspecialchars($aluno['alergias']); ?></p>
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
                                    
                                    <?php if ($aluno['observacoes']): ?>
                                    <div class="mb-3">
                                        <strong>Observações:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($aluno['observacoes'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-check-circle text-success me-2"></i>
                                        Aprovar Cadastro
                                    </h4>
                                    <p class="text-muted">Este cadastro está completo e pronto para aprovação.</p>
                                    
                                    <form method="POST" action="aprovar.php?id=<?php echo $aluno_id; ?>">
                                        <input type="hidden" name="acao" value="aprovar">
                                        <button type="submit" class="btn btn-gradient-success w-100 mb-3">
                                            <i class="mdi mdi-check"></i> Aprovar Cadastro
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <h5 class="mb-3">
                                        <i class="mdi mdi-close-circle text-danger me-2"></i>
                                        Rejeitar Cadastro
                                    </h5>
                                    <p class="text-muted">Se houver problemas nos dados, você pode rejeitar para correção.</p>
                                    
                                    <form method="POST" action="aprovar.php?id=<?php echo $aluno_id; ?>">
                                        <input type="hidden" name="acao" value="rejeitar">
                                        <div class="mb-3">
                                            <label for="motivo_rejeicao" class="form-label">Motivo da Rejeição *</label>
                                            <textarea class="form-control" id="motivo_rejeicao" name="motivo_rejeicao" rows="3" required placeholder="Descreva o motivo da rejeição..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-gradient-danger w-100">
                                            <i class="mdi mdi-close"></i> Rejeitar Cadastro
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="index.php" class="btn btn-light">
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
