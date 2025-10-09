<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

// Processar aprovação de teste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'aprovar_teste') {
        $aluno_id = (int)($_POST['aluno_id'] ?? 0);
        
        if ($aluno_id > 0) {
            try {
                $pdo->beginTransaction();
                
                // Verificar se o aluno existe e está com status 'completo'
                $stmt = $pdo->prepare("SELECT id, nome, status_cadastro FROM alunos WHERE id = ?");
                $stmt->execute([$aluno_id]);
                $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($aluno && $aluno['status_cadastro'] === 'completo') {
                    // Atualizar status do aluno para aprovado
                    $stmt = $pdo->prepare("UPDATE alunos SET status_cadastro = 'aprovado' WHERE id = ?");
                    $stmt->execute([$aluno_id]);
                    
                    // Atualizar status no controle
                    $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET status = 'aprovado' WHERE aluno_id = ?");
                    $stmt->execute([$aluno_id]);
                    
                    $pdo->commit();
                    
                    $sucesso = "✅ Pré-cadastro aprovado com sucesso!<br>";
                    $sucesso .= "👤 Aluno: " . htmlspecialchars($aluno['nome']) . "<br>";
                    $sucesso .= "🆔 ID: " . $aluno_id . "<br>";
                    $sucesso .= "📅 Data: " . date('d/m/Y H:i:s');
                    
                } else {
                    $pdo->rollBack();
                    $erro = "❌ Erro: Aluno não encontrado ou status inválido para aprovação.<br>";
                    if ($aluno) {
                        $erro .= "Status atual: " . $aluno['status_cadastro'];
                    } else {
                        $erro .= "Aluno não encontrado.";
                    }
                }
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $erro = "❌ Erro ao aprovar pré-cadastro: " . $e->getMessage();
                error_log("Erro ao aprovar pré-cadastro: " . $e->getMessage());
            }
        } else {
            $erro = "❌ ID do aluno inválido.";
        }
    }
}

// Buscar alunos com status 'completo' para teste
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.status_cadastro, a.codigo_pre_cadastro,
               t.nome as turma_nome, pc.criado_em
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        WHERE a.status_cadastro = 'completo'
        ORDER BY pc.criado_em DESC
        LIMIT 10
    ");
    $stmt->execute();
    $alunos_completos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alunos_completos = [];
    error_log("Erro ao buscar alunos: " . $e->getMessage());
}

// Buscar todos os alunos para referência
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.status_cadastro, a.codigo_pre_cadastro,
               t.nome as turma_nome
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        ORDER BY a.id DESC
        LIMIT 20
    ");
    $stmt->execute();
    $todos_alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $todos_alunos = [];
    error_log("Erro ao buscar todos os alunos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Aprovação - Sistema Escolar</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
    <style>
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .test-body {
            padding: 20px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pre_cadastro { background: #ffc107; color: #000; }
        .status-completo { background: #17a2b8; color: #fff; }
        .status-aprovado { background: #28a745; color: #fff; }
        .btn-test {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        .alert-custom {
            border-radius: 8px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
        .table-custom {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table-custom th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }
        .table-custom td {
            border: none;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <!-- Header -->
        <div class="test-card">
            <div class="test-header">
                <h1><i class="mdi mdi-test-tube"></i> Teste de Aprovação</h1>
                <p class="mb-0">Página para testar a funcionalidade de aprovação de pré-cadastros</p>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if (isset($sucesso)): ?>
        <div class="alert alert-success alert-custom">
            <h5><i class="mdi mdi-check-circle"></i> Sucesso!</h5>
            <?php echo $sucesso; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-custom">
            <h5><i class="mdi mdi-alert-circle"></i> Erro!</h5>
            <?php echo $erro; ?>
        </div>
        <?php endif; ?>

        <!-- Alunos Prontos para Aprovação -->
        <div class="test-card">
            <div class="test-body">
                <h3><i class="mdi mdi-check-circle text-success"></i> Alunos Prontos para Aprovação</h3>
                <p class="text-muted">Alunos com status 'completo' que podem ser aprovados</p>
                
                <?php if (empty($alunos_completos)): ?>
                <div class="alert alert-info">
                    <i class="mdi mdi-information"></i> Nenhum aluno com status 'completo' encontrado.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Turma</th>
                                <th>Status</th>
                                <th>Código</th>
                                <th>Criado em</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos_completos as $aluno): ?>
                            <tr>
                                <td><strong><?php echo $aluno['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                <td><?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não definida'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $aluno['status_cadastro']; ?>">
                                        <?php echo ucfirst($aluno['status_cadastro']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($aluno['codigo_pre_cadastro']): ?>
                                    <code><?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?></code>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($aluno['criado_em']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($aluno['criado_em'])); ?>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja aprovar o aluno <?php echo htmlspecialchars($aluno['nome']); ?>?')">
                                        <input type="hidden" name="acao" value="aprovar_teste">
                                        <input type="hidden" name="aluno_id" value="<?php echo $aluno['id']; ?>">
                                        <button type="submit" class="btn btn-test">
                                            <i class="mdi mdi-check"></i> Aprovar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Todos os Alunos (Referência) -->
        <div class="test-card">
            <div class="test-body">
                <h3><i class="mdi mdi-account-group"></i> Todos os Alunos (Referência)</h3>
                <p class="text-muted">Lista de todos os alunos para referência</p>
                
                <?php if (empty($todos_alunos)): ?>
                <div class="alert alert-info">
                    <i class="mdi mdi-information"></i> Nenhum aluno encontrado.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Turma</th>
                                <th>Status</th>
                                <th>Código</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos_alunos as $aluno): ?>
                            <tr>
                                <td><strong><?php echo $aluno['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                <td><?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não definida'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $aluno['status_cadastro']; ?>">
                                        <?php echo ucfirst($aluno['status_cadastro']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($aluno['codigo_pre_cadastro']): ?>
                                    <code><?php echo htmlspecialchars($aluno['codigo_pre_cadastro']); ?></code>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informações do Sistema -->
        <div class="test-card">
            <div class="test-body">
                <h3><i class="mdi mdi-information"></i> Informações do Sistema</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Status Possíveis:</h5>
                        <ul>
                            <li><span class="status-badge status-pre_cadastro">Pre_cadastro</span> - Cadastro inicial</li>
                            <li><span class="status-badge status-completo">Completo</span> - Dados preenchidos pelo responsável</li>
                            <li><span class="status-badge status-aprovado">Aprovado</span> - Aprovado pela secretaria/financeiro</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Funcionalidades:</h5>
                        <ul>
                            <li>✅ Aprovação de alunos com status 'completo'</li>
                            <li>✅ Atualização automática do status</li>
                            <li>✅ Confirmação antes da aprovação</li>
                            <li>✅ Feedback visual de sucesso/erro</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <div class="test-card">
            <div class="test-body text-center">
                <h3><i class="mdi mdi-navigation"></i> Navegação</h3>
                <div class="btn-group" role="group">
                    <a href="secretaria/pre_cadastro/index.php" class="btn btn-outline-primary">
                        <i class="mdi mdi-office-building"></i> Secretaria
                    </a>
                    <a href="financeiro/pre_cadastro/index.php" class="btn btn-outline-success">
                        <i class="mdi mdi-currency-usd"></i> Financeiro
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="mdi mdi-view-dashboard"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh da página a cada 30 segundos para mostrar atualizações
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Adicionar animação aos botões
        document.querySelectorAll('.btn-test').forEach(btn => {
            btn.addEventListener('click', function() {
                this.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Processando...';
                this.disabled = true;
            });
        });
    </script>
</body>
</html>
