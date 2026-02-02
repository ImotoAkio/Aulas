<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/webhook_functions.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

// Processar aprovação direta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    error_log("DEBUG: POST recebido - acao: " . ($_POST['acao'] ?? 'não definido'));

    if ($_POST['acao'] === 'aprovar_direto') {
        $aluno_id = (int) ($_POST['aluno_id'] ?? 0);
        error_log("DEBUG: Tentando aprovar aluno ID: " . $aluno_id);

        if ($aluno_id > 0) {
            try {
                $pdo->beginTransaction();

                // Verificar se o aluno está com status 'completo'
                $stmt = $pdo->prepare("SELECT status_cadastro FROM alunos WHERE id = ?");
                $stmt->execute([$aluno_id]);
                $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

                error_log("DEBUG: Status atual do aluno: " . ($aluno['status_cadastro'] ?? 'não encontrado'));

                if ($aluno && $aluno['status_cadastro'] === 'completo') {
                    // Atualizar status do aluno para aprovado
                    $stmt = $pdo->prepare("UPDATE alunos SET status_cadastro = 'aprovado' WHERE id = ?");
                    $stmt->execute([$aluno_id]);

                    // Atualizar status no controle
                    $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET status = 'aprovado' WHERE aluno_id = ?");
                    $stmt->execute([$aluno_id]);

                    $pdo->commit();

                    // Enviar webhook de aprovação
                    $webhook_enviado = enviarWebhookAprovacao($aluno_id, $pdo);

                    error_log("DEBUG: Aluno aprovado com sucesso!");
                    $sucesso = "Pré-cadastro aprovado com sucesso! O aluno está oficialmente matriculado.";
                    if ($webhook_enviado) {
                        $sucesso .= " Notificação enviada via webhook.";
                    } else {
                        $sucesso .= " <small class='text-warning'>(Webhook não configurado ou falhou)</small>";
                    }
                } else {
                    $pdo->rollBack();
                    error_log("DEBUG: Erro - aluno não encontrado ou status inválido");
                    $erro = "Erro: Aluno não encontrado ou status inválido para aprovação.";
                }

            } catch (PDOException $e) {
                $pdo->rollBack();
                $erro = "Erro ao aprovar pré-cadastro: " . $e->getMessage();
                error_log("Erro ao aprovar pré-cadastro: " . $e->getMessage());
            }
        } else {
            $erro = "ID do aluno inválido.";
        }
    }

    // Processar exclusão
    if ($_POST['acao'] === 'excluir') {
        $aluno_id = (int) ($_POST['aluno_id'] ?? 0);

        if ($aluno_id > 0) {
            try {
                $pdo->beginTransaction();

                // Excluir do controle
                $stmt = $pdo->prepare("DELETE FROM pre_cadastros_controle WHERE aluno_id = ?");
                $stmt->execute([$aluno_id]);

                // Excluir aluno
                $stmt = $pdo->prepare("DELETE FROM alunos WHERE id = ?");
                $stmt->execute([$aluno_id]);

                $pdo->commit();
                $sucesso = "Pré-cadastro excluído com sucesso.";

            } catch (PDOException $e) {
                $pdo->rollBack();
                $erro = "Erro ao excluir pré-cadastro: " . $e->getMessage();
                error_log("Erro ao excluir pré-cadastro: " . $e->getMessage());
            }
        } else {
            $erro = "ID do aluno inválido.";
        }
    }
}

// Buscar pré-cadastros pendentes
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.codigo_pre_cadastro, a.status_cadastro, a.preenchido_por_responsavel, 
               a.dados_preenchidos_em, t.nome as turma_nome, pc.link_expiracao, pc.observacoes, pc.criado_em
        FROM alunos a
        INNER JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.status_cadastro IN ('pre_cadastro', 'completo', 'aprovado')
        ORDER BY pc.criado_em DESC
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
                            Pré-cadastros de Alunosa
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo getPageUrl('secretaria/index.php'); ?>">SecretASDSAaria</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Pré-cadastros</li>
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

                    <!-- Botão para criar novo pré-cadastro -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#modalNovoPreCadastro">
                                <i class="mdi mdi-plus"></i> Novo Pré-cadastro
                            </button>
                            <a href="gerar_json.php" class="btn btn-gradient-success ms-2">
                                <i class="mdi mdi-code-json"></i> Exportar Dados (JSON)
                            </a>
                        </div>
                    </div>

                    <!-- Cards de resumo -->
                    <div class="row">
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Pendentes</h4>
                                    <h2 class="text-warning mb-0">
                                        <?php echo count(array_filter($pre_cadastros, function ($p) {
                                            return $p['status_cadastro'] === 'pre_cadastro'; })); ?>
                                    </h2>
                                    <small class="text-muted">Aguardando preenchimento</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Completos</h4>
                                    <h2 class="text-info mb-0">
                                        <?php echo count(array_filter($pre_cadastros, function ($p) {
                                            return $p['status_cadastro'] === 'completo'; })); ?>
                                    </h2>
                                    <small class="text-muted">Aguardando aprovação</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Aprovados</h4>
                                    <h2 class="text-success mb-0">
                                        <?php echo count(array_filter($pre_cadastros, function ($p) {
                                            return $p['status_cadastro'] === 'aprovado'; })); ?>
                                    </h2>
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
                                                    <th>Preenchido</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pre_cadastros as $pre_cadastro): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($pre_cadastro['nome']); ?></strong>
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
                            <input type="tel" class="form-control" id="telefoneWhatsApp" placeholder="(87) 99999-9999"
                                required>
                            <small class="form-text text-muted">
                                <i class="mdi mdi-information"></i>
                                Digite o número que receberá os dados via WhatsApp.
                            </small>
                        </div>
                        <div class="alert alert-info">
                            <h6><i class="mdi mdi-information"></i> Dados que serão enviados:</h6>
                            <ul class="mb-0">
                                <li><strong>Link do Cadastro:</strong> <span id="linkCadastroPreview"></span></li>
                                <li><strong>Nome do Aluno:</strong> <span id="nomeAlunoPreview"></span></li>
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

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="modalExcluirLabel">
                        <i class="mdi mdi-alert-circle-outline me-2"></i>Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o pré-cadastro de <strong id="nomeAlunoExcluir"></strong>?</p>
                    <p class="text-muted small">Esta ação não pode ser desfeita e removerá todos os dados associados.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="aluno_id" id="idAlunoExcluir">
                        <button type="submit" class="btn btn-danger">Excluir Definitivamente</button>
                    </form>
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

    <script>
        // Variáveis globais para o modal
        let alunoIdAtual = null;
        let codigoAtual = null;

        function copiarLink(codigo) {
            const link = '<?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=' + codigo;
            navigator.clipboard.writeText(link).then(function () {
                alert('Link copiado para a área de transferência!');
            });
        }

        function abrirModalWhatsApp(alunoId, nomeAluno, codigo) {
            alunoIdAtual = alunoId;
            codigoAtual = codigo;

            // Atualizar preview no modal
            document.getElementById('nomeAlunoPreview').textContent = nomeAluno;
            document.getElementById('linkCadastroPreview').textContent = '<?php echo getBaseUrl(); ?>cadastro_aluno.php?codigo=' + codigo;

            // Limpar campo de telefone
            document.getElementById('telefoneWhatsApp').value = '';

            // Esconder status
            document.getElementById('whatsapp-status').style.display = 'none';

            // Abrir modal
            $('#modalWhatsApp').modal('show');
        }

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
            fetch('visualizar.php?id=' + alunoIdAtual, {
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
                        mensagemDiv.innerHTML = '<i class="mdi mdi-alert"></i> Webhook não configurado. Configure em Configurações Avançadas.';
                    } else {
                        alertDiv.className = 'alert alert-danger';
                        mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle"></i> <strong>Erro!</strong><br>' + (data.webhook_erro || 'Erro desconhecido');
                    }
                })
                .catch(error => {
                    alertDiv.className = 'alert alert-danger';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle"></i> <strong>Erro de conexão!</strong><br>' + error.message;
                });
        }
        function confirmarExclusao(id, nome) {
            document.getElementById('idAlunoExcluir').value = id;
            document.getElementById('nomeAlunoExcluir').textContent = nome;
            $('#modalExcluir').modal('show');
        }
    </script>
</body>

</html>