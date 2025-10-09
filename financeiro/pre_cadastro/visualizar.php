<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/webhook_functions.php';

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
        WHERE a.id = ?
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'aprovar' && $aluno['status_cadastro'] === 'completo') {
        try {
            $stmt = $pdo->prepare("UPDATE alunos SET status_cadastro = 'aprovado' WHERE id = ?");
            $stmt->execute([$aluno_id]);
            
            $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET status = 'aprovado' WHERE aluno_id = ?");
            $stmt->execute([$aluno_id]);
            
            // Enviar webhook de aprovação
            $webhook_enviado = enviarWebhookAprovacao($aluno_id, $pdo);
            
            $sucesso = "Pré-cadastro aprovado com sucesso!";
            if ($webhook_enviado) {
                $sucesso .= " Notificação enviada via webhook.";
            } else {
                $sucesso .= " <small class='text-warning'>(Webhook não configurado ou falhou)</small>";
            }
            $aluno['status_cadastro'] = 'aprovado';
            
        } catch (PDOException $e) {
            $erro = "Erro ao aprovar pré-cadastro: " . $e->getMessage();
            error_log("Erro ao aprovar pré-cadastro: " . $e->getMessage());
        }
    }
}

// Processar envio de JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar_json') {
    try {
        // Verificar se foi enviado telefone customizado
        if (isset($_POST['telefone_custom']) && !empty($_POST['telefone_custom'])) {
            $telefone_formatado = $_POST['telefone_custom'];
        } else {
            // Buscar telefone do aluno
            $telefone = $aluno['telefone1'] ?? $aluno['telefone2'] ?? '';
            
            // Formatar telefone para o padrão solicitado (5587991682773)
            $telefone_formatado = '';
            if ($telefone) {
                // Remover caracteres não numéricos
                $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
                
                // Se não começar com 55, adicionar código do Brasil
                if (!str_starts_with($telefone_limpo, '55')) {
                    $telefone_formatado = '55' . $telefone_limpo;
                } else {
                    $telefone_formatado = $telefone_limpo;
                }
            }
        }
        
        // Gerar link do cadastro
        $base_url = getBaseUrl();
        $link_cadastro = $base_url . 'cadastro_aluno.php?codigo=' . $aluno['codigo_pre_cadastro'];
        
        // Criar JSON
        $json_data = [
            'link_cadastro' => $link_cadastro,
            'telefone' => $telefone_formatado,
            'aluno_nome' => $aluno['nome'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Buscar URL do webhook configurada
        $webhook_url = '';
        try {
            $stmt = $pdo->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'webhook_url'");
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado && !empty($resultado['valor'])) {
                $webhook_url = $resultado['valor'];
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar webhook: " . $e->getMessage());
        }
        
        // Se webhook estiver configurado, enviar dados
        if ($webhook_url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhook_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: Sistema-Escolar/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($curl_error) {
                $json_data['webhook_status'] = 'erro';
                $json_data['webhook_erro'] = $curl_error;
            } else {
                $json_data['webhook_status'] = $http_code >= 200 && $http_code < 300 ? 'sucesso' : 'erro';
                $json_data['webhook_response'] = $response;
                $json_data['webhook_http_code'] = $http_code;
            }
        } else {
            $json_data['webhook_status'] = 'nao_configurado';
            $json_data['webhook_mensagem'] = 'Webhook não configurado. Configure em Configurações Avançadas.';
        }
        
        // Retornar JSON
        header('Content-Type: application/json');
        echo json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Erro ao gerar JSON: ' . $e->getMessage()]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Pré-cadastro - Financeiro</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
    
    <style>
        /* Garantir que os botões sejam visíveis */
        .btn {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .btn-primary {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }
        
        .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }
        
        .btn-outline-secondary {
            color: #6c757d !important;
            border-color: #6c757d !important;
        }
        
        .btn-outline-info {
            color: #17a2b8 !important;
            border-color: #17a2b8 !important;
        }
        
        .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
        }
        
        .btn-warning {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #212529 !important;
        }
        
        .ml-2 {
            margin-left: 0.5rem !important;
        }
        
        .w-100 {
            width: 100% !important;
        }
        
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        
        .mt-3 {
            margin-top: 1rem !important;
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
                                <i class="mdi mdi-eye"></i>
                            </span>
                            Visualizar Pré-cadastro
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Financeiro</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/pre_cadastro/index.php'); ?>">Pré-cadastros</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Visualizar</li>
                            </ul>
                        </nav>
                    </div>

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
                                    
                                    <!-- Botões de ação -->
                                    <div class="mb-3">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="copiarLink()" title="Copiar link do cadastro">
                                                <i class="mdi mdi-content-copy"></i> Copiar Link
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalWhatsApp">
                                                <i class="mdi mdi-whatsapp"></i> Enviar para WhatsApp
                                            </button>
                                        </div>
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
                        </div>
                    </div>
                    
                    <?php if ($aluno['status_cadastro'] === 'completo'): ?>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-check-circle text-success me-2"></i>
                                        Aprovar Cadastro
                                    </h4>
                                    <p class="text-muted">Este cadastro está completo e pronto para aprovação.</p>
                                    
                                    <form method="POST" action="visualizar.php?id=<?php echo $aluno_id; ?>">
                                        <input type="hidden" name="acao" value="aprovar">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="mdi mdi-check"></i> Aprovar Cadastro
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="<?php echo getPageUrl('financeiro/pre_cadastro/index.php'); ?>" class="btn btn-light">
                                <i class="mdi mdi-arrow-left"></i> Voltar para Lista
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Envio via WhatsApp -->
    <div class="modal fade" id="modalWhatsApp" tabindex="-1" role="dialog" aria-labelledby="modalWhatsAppLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalWhatsAppLabel">
                        <i class="mdi mdi-whatsapp text-success me-2"></i>
                        Enviar para WhatsApp
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formWhatsApp">
                        <div class="form-group">
                            <label for="telefoneWhatsApp">Número do WhatsApp</label>
                            <input type="tel"
                                   class="form-control"
                                   id="telefoneWhatsApp"
                                   placeholder="(87) 99999-9999"
                                   required>
                            <small class="form-text text-muted">
                                <i class="mdi mdi-information"></i>
                                Digite o número que receberá os dados via WhatsApp.
                            </small>
                        </div>
                        <div class="alert alert-info">
                            <h6><i class="mdi mdi-information"></i> Dados que serão enviados:</h6>
                            <ul class="mb-0">
                                <li><strong>Link do Cadastro:</strong> <?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=<?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?></li>
                                <li><strong>Nome do Aluno:</strong> <?php echo htmlspecialchars($aluno['nome']); ?></li>
                                <li><strong>Telefone:</strong> Será formatado automaticamente</li>
                                <li><strong>Timestamp:</strong> Data/hora atual</li>
                            </ul>
                        </div>
                        <div id="whatsapp-status" style="display: none;">
                            <div class="alert" id="whatsapp-alert">
                                <div id="whatsapp-mensagem"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="mdi mdi-close"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="enviarParaWhatsApp()">
                        <i class="mdi mdi-whatsapp"></i> Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Envio via Webhook -->
    <div class="modal fade" id="modalEnviarWebhook" tabindex="-1" role="dialog" aria-labelledby="modalEnviarWebhookLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEnviarWebhookLabel">
                        <i class="mdi mdi-send text-success me-2"></i>
                        Enviar via Webhook
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formWebhook">
                        <div class="form-group">
                            <label for="telefoneWebhook">Telefone para Envio</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="telefoneWebhook" 
                                   placeholder="(87) 99999-9999"
                                   required>
                            <small class="form-text text-muted">
                                <i class="mdi mdi-information"></i>
                                Digite o telefone que receberá os dados via webhook.
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="mdi mdi-information"></i> Dados que serão enviados:</h6>
                            <ul class="mb-0">
                                <li><strong>Link do Cadastro:</strong> <?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=<?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?></li>
                                <li><strong>Nome do Aluno:</strong> <?php echo htmlspecialchars($aluno['nome']); ?></li>
                                <li><strong>Telefone:</strong> Será formatado automaticamente</li>
                                <li><strong>Timestamp:</strong> Data/hora atual</li>
                            </ul>
                        </div>
                        
                        <div id="webhook-status" style="display: none;">
                            <div class="alert" id="webhook-alert">
                                <div id="webhook-mensagem"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="mdi mdi-close"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="enviarViaWebhook()">
                        <i class="mdi mdi-send"></i> Enviar
                    </button>
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
        let jsonData = null;
        
        // Interceptar o formulário de JSON
        document.getElementById('form-json').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                jsonData = data;
                document.getElementById('json-content').textContent = JSON.stringify(data, null, 2);
                document.getElementById('json-result').style.display = 'block';
                
                // Scroll para o JSON
                document.getElementById('json-result').scrollIntoView({ behavior: 'smooth' });
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao gerar JSON: ' + error.message);
            });
        });
        
        // Função para copiar JSON
        function copiarJson() {
            if (!jsonData) {
                alert('Primeiro gere o JSON clicando em "Gerar JSON"');
                return;
            }
            
            const jsonString = JSON.stringify(jsonData, null, 2);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(jsonString).then(() => {
                    alert('JSON copiado para a área de transferência!');
                }).catch(err => {
                    console.error('Erro ao copiar:', err);
                    fallbackCopyTextToClipboard(jsonString);
                });
            } else {
                fallbackCopyTextToClipboard(jsonString);
            }
        }
        
        // Fallback para navegadores que não suportam clipboard API
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('JSON copiado para a área de transferência!');
                } else {
                    alert('Erro ao copiar JSON');
                }
            } catch (err) {
                console.error('Fallback: Erro ao copiar', err);
                alert('Erro ao copiar JSON');
            }
            
            document.body.removeChild(textArea);
        }
        
        // Função para copiar link do cadastro
        function copiarLink() {
            const linkCadastro = "<?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=<?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?>";
            
            // Usar Clipboard API se disponível
            if (navigator.clipboard) {
                navigator.clipboard.writeText(linkCadastro).then(() => {
                    // Mostrar feedback visual
                    const btn = event.target.closest('button');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="mdi mdi-check"></i> Copiado!';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-success');
                    
                    // Restaurar após 2 segundos
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-primary');
                    }, 2000);
                }).catch(err => {
                    console.error('Erro ao copiar:', err);
                    fallbackCopyTextToClipboard(linkCadastro);
                });
            } else {
                // Fallback para navegadores que não suportam clipboard API
                fallbackCopyTextToClipboard(linkCadastro);
            }
        }
        
        // Fallback para copiar texto
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('Link copiado para a área de transferência!');
                } else {
                    alert('Erro ao copiar link');
                }
            } catch (err) {
                console.error('Fallback: Erro ao copiar', err);
                alert('Erro ao copiar link');
            }
            
            document.body.removeChild(textArea);
        }
        
        // Função para enviar via WhatsApp
        function enviarParaWhatsApp() {
            const telefone = document.getElementById('telefoneWhatsApp').value;
            const statusDiv = document.getElementById('whatsapp-status');
            const alertDiv = document.getElementById('whatsapp-alert');
            const mensagemDiv = document.getElementById('whatsapp-mensagem');
            
            if (!telefone) {
                alert('❌ Por favor, digite o número do WhatsApp!');
                return;
            }
            
            // Limpar e formatar telefone
            const telefoneLimpo = telefone.replace(/[^0-9]/g, '');
            let telefoneFormatado = '';
            
            if (!telefoneLimpo.startsWith('55')) {
                telefoneFormatado = '55' + telefoneLimpo;
            } else {
                telefoneFormatado = telefoneLimpo;
            }
            
            // Mostrar status
            statusDiv.style.display = 'block';
            alertDiv.className = 'alert alert-info';
            mensagemDiv.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Enviando dados para WhatsApp...';
            
            // Enviar via AJAX
            fetch('visualizar.php?id=<?php echo $aluno_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=enviar_json&telefone_custom=' + encodeURIComponent(telefoneFormatado)
            })
            .then(response => response.json())
            .then(data => {
                if (data.webhook_status === 'sucesso') {
                    alertDiv.className = 'alert alert-success';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-check-circle"></i> <strong>Sucesso!</strong><br>Dados enviados para WhatsApp: ' + telefoneFormatado;
                    
                    // Fechar modal após 2 segundos
                    setTimeout(() => {
                        $('#modalWhatsApp').modal('hide');
                        // Limpar campo
                        document.getElementById('telefoneWhatsApp').value = '';
                        statusDiv.style.display = 'none';
                    }, 2000);
                } else if (data.webhook_status === 'nao_configurado') {
                    alertDiv.className = 'alert alert-warning';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-alert"></i> <strong>Atenção!</strong><br>Webhook não configurado. Configure em Configurações Avançadas.';
                } else {
                    alertDiv.className = 'alert alert-danger';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-alert-circle"></i> <strong>Erro!</strong><br>' + (data.webhook_erro || data.webhook_response || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alertDiv.className = 'alert alert-danger';
                mensagemDiv.innerHTML = '<i class="mdi mdi-alert-circle"></i> <strong>Erro de conexão!</strong><br>' + error.message;
            });
        }

        function enviarViaWebhook() {
            const telefone = document.getElementById('telefoneWebhook').value;
            const statusDiv = document.getElementById('webhook-status');
            const alertDiv = document.getElementById('webhook-alert');
            const mensagemDiv = document.getElementById('webhook-mensagem');
            
            if (!telefone) {
                alert('Por favor, digite o telefone.');
                return;
            }
            
            // Formatar telefone
            const telefoneLimpo = telefone.replace(/[^0-9]/g, '');
            const telefoneFormatado = telefoneLimpo.startsWith('55') ? telefoneLimpo : '55' + telefoneLimpo;
            
            // Dados para envio
            const dadosEnvio = {
                link_cadastro: "<?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=<?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?>",
                telefone: telefoneFormatado,
                aluno_nome: "<?php echo htmlspecialchars($aluno['nome']); ?>",
                timestamp: new Date().toISOString().slice(0, 19).replace('T', ' ')
            };
            
            // Mostrar loading
            alertDiv.className = 'alert alert-info';
            mensagemDiv.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Enviando dados via webhook...';
            statusDiv.style.display = 'block';
            
            // Fazer requisição para o webhook
            fetch('visualizar.php?id=<?php echo $aluno_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=enviar_json&telefone_custom=' + encodeURIComponent(telefoneFormatado)
            })
            .then(response => response.json())
            .then(data => {
                if (data.webhook_status === 'sucesso') {
                    alertDiv.className = 'alert alert-success';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-check-circle"></i> Dados enviados com sucesso via webhook!';
                    
                    // Fechar modal após 2 segundos
                    setTimeout(() => {
                        $('#modalEnviarWebhook').modal('hide');
                        statusDiv.style.display = 'none';
                        document.getElementById('telefoneWebhook').value = '';
                    }, 2000);
                } else if (data.webhook_status === 'nao_configurado') {
                    alertDiv.className = 'alert alert-warning';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-alert"></i> Webhook não configurado. Configure em Configurações Avançadas.';
                } else {
                    alertDiv.className = 'alert alert-danger';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle"></i> Erro ao enviar: ' + (data.webhook_erro || 'Erro desconhecido');
                }
            })
            .catch(error => {
                alertDiv.className = 'alert alert-danger';
                mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle"></i> Erro de conexão: ' + error.message;
            });
        }
        
        // Função para testar webhook
        function testarWebhook() {
            const statusDiv = document.getElementById('webhook-status');
            const alertDiv = document.getElementById('webhook-alert');
            const mensagemDiv = document.getElementById('webhook-mensagem');
            
            // Mostrar loading
            alertDiv.className = 'alert alert-info';
            mensagemDiv.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Testando webhook...';
            statusDiv.style.display = 'block';
            
            // Fazer requisição de teste
            fetch('visualizar.php?id=<?php echo $aluno_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=enviar_json&telefone_custom=5587991682773'
            })
            .then(response => response.json())
            .then(data => {
                if (data.webhook_status === 'sucesso') {
                    alertDiv.className = 'alert alert-success';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-check-circle"></i> Webhook funcionando corretamente!';
                } else if (data.webhook_status === 'nao_configurado') {
                    alertDiv.className = 'alert alert-warning';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-alert"></i> Webhook não configurado. Configure em Configurações Avançadas.';
                } else {
                    alertDiv.className = 'alert alert-danger';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle"></i> Erro no webhook: ' + (data.webhook_erro || 'Erro desconhecido');
                }
            })
            .catch(error => {
                alertDiv.className = 'alert alert-danger';
                mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle"></i> Erro de conexão: ' + error.message;
            });
        }
    </script>
    
    <!-- Scripts necessários para Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para inicializar modais -->
    <script>
        $(document).ready(function() {
            // Garantir que os modais funcionem
            $('[data-toggle="modal"]').on('click', function() {
                var target = $(this).data('target');
                $(target).modal('show');
            });
        });
    </script>
</body>
</html>
