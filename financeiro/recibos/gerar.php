<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

$pdo = getConnection();
$erro = '';
$sucesso = '';

// Processar formulário de geração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_recibo'])) {
    $aluno_id = (int)($_POST['aluno_id'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? '');
    $referencia = trim($_POST['referencia'] ?? '');
    $valor_original = (float)($_POST['valor_original'] ?? 0);
    $desconto = (float)($_POST['desconto'] ?? 0);
    $acrescimos = (float)($_POST['acrescimos'] ?? 0);
    $vencimento = $_POST['vencimento'] ?? null;
    $descricao = trim($_POST['descricao'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    $valor_final = $valor_original - $desconto + $acrescimos;
    
    // Verificar se está usando itens
    $usar_itens = isset($_POST['usar_itens']) && $_POST['usar_itens'] === '1';
    $itens = [];
    
    // Processar itens se estiver usando
    if ($usar_itens && isset($_POST['itens']) && is_array($_POST['itens'])) {
        foreach ($_POST['itens'] as $item) {
            if (!empty($item['descricao']) && !empty($item['valor_unitario'])) {
                $itens[] = [
                    'descricao' => trim($item['descricao']),
                    'quantidade' => (float)($item['quantidade'] ?? 1),
                    'unidade' => trim($item['unidade'] ?? ''),
                    'valor_unitario' => (float)$item['valor_unitario'],
                    'valor_total' => ((float)($item['quantidade'] ?? 1)) * ((float)$item['valor_unitario'])
                ];
            }
        }
    }
    
    // Calcular valor se estiver usando itens
    if ($usar_itens && count($itens) > 0) {
        $valor_original = array_sum(array_column($itens, 'valor_total'));
        $valor_final = $valor_original - $desconto + $acrescimos;
    }
    
    // Validações
    if ($aluno_id <= 0) {
        $erro = 'Selecione um aluno.';
    } elseif (!in_array($tipo, ['mensalidade', 'fardamento', 'atividade', 'matricula'])) {
        $erro = 'Selecione um tipo válido de recibo.';
    } elseif ($usar_itens && count($itens) === 0) {
        $erro = 'Adicione pelo menos um item ao recibo.';
    } elseif (!$usar_itens && $valor_original <= 0) {
        $erro = 'O valor original deve ser maior que zero.';
    } elseif ($valor_final < 0) {
        $erro = 'O valor final não pode ser negativo.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Obter próximo número de recibo
            $stmtNumero = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_recibo, 5) AS UNSIGNED)), 0) as ultimo_num FROM recibos WHERE numero_recibo IS NOT NULL");
            $ultimoNum = $stmtNumero->fetch(PDO::FETCH_ASSOC)['ultimo_num'];
            $numero_recibo = 'REC-' . str_pad($ultimoNum + 1, 6, '0', STR_PAD_LEFT);
            
            // Inserir recibo
            $stmt = $pdo->prepare("
                INSERT INTO recibos (
                    aluno_id, tipo, referencia, valor_original, desconto, acrescimos, 
                    valor_final, vencimento, descricao, observacoes, numero_recibo, 
                    gerado_por, status
                ) VALUES (
                    :aluno_id, :tipo, :referencia, :valor_original, :desconto, :acrescimos,
                    :valor_final, :vencimento, :descricao, :observacoes, :numero_recibo,
                    :usuario_id, 'gerado'
                )
            ");
            
            $stmt->execute([
                ':aluno_id' => $aluno_id,
                ':tipo' => $tipo,
                ':referencia' => $referencia,
                ':valor_original' => $valor_original,
                ':desconto' => $desconto,
                ':acrescimos' => $acrescimos,
                ':valor_final' => $valor_final,
                ':vencimento' => $vencimento ?: null,
                ':descricao' => $descricao,
                ':observacoes' => $observacoes,
                ':numero_recibo' => $numero_recibo,
                ':usuario_id' => $_SESSION['usuario_id']
            ]);
            
            $recibo_id = $pdo->lastInsertId();
            
            // Inserir itens se houver
            if (count($itens) > 0) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO recibo_itens (
                        recibo_id, descricao, quantidade, unidade, valor_unitario, valor_total, ordem
                    ) VALUES (
                        :recibo_id, :descricao, :quantidade, :unidade, :valor_unitario, :valor_total, :ordem
                    )
                ");
                
                foreach ($itens as $ordem => $item) {
                    $stmtItem->execute([
                        ':recibo_id' => $recibo_id,
                        ':descricao' => $item['descricao'],
                        ':quantidade' => $item['quantidade'],
                        ':unidade' => $item['unidade'],
                        ':valor_unitario' => $item['valor_unitario'],
                        ':valor_total' => $item['valor_total'],
                        ':ordem' => $ordem
                    ]);
                }
            }
            
            $pdo->commit();
            $sucesso = "Recibo gerado com sucesso! Número: $numero_recibo";
            
            // Redirecionar para a página de visualização/impressão
            header('Location: gerar_pdf.php?id=' . $recibo_id);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = 'Erro ao gerar recibo: ' . $e->getMessage();
            error_log("Erro ao gerar recibo: " . $e->getMessage());
        }
    }
}

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

// Buscar dados do aluno selecionado (via GET)
$aluno_selecionado = null;
if (isset($_GET['aluno_id']) && !empty($_GET['aluno_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.id, a.nome, a.nome_completo, a.cpf, a.rg, a.data_nascimento,
                a.nome_resp_legal, a.cpf_resp_legal, a.profissao_resp_legal,
                a.endereco, a.numero, a.complemento, a.bairro, a.cidade, a.estado,
                a.telefone1, a.telefone2, a.email,
                t.nome as turma_nome, t.ano_letivo
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            WHERE a.id = ?
        ");
        $stmt->execute([$_GET['aluno_id']]);
        $aluno_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar aluno: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Recibo - Financeiro</title>
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
            min-width: 150px;
        }
        .info-value {
            color: #666;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include __DIR__ . '/../partials/_navbar.php'; ?>
        <div class="container-fluid page-body-wrapper">
            <?php include __DIR__ . '/../partials/_sidebar.php'; ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Gerar Novo Recibo</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="index.php">Recibos</a></li>
                                <li class="breadcrumb-item active">Gerar Recibo</li>
                            </ol>
                        </nav>
                    </div>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="gerar.php">
                        <div class="row">
                            <!-- PASSO 1: SELEÇÃO DO ALUNO -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">
                                            <i class="mdi mdi-account-multiple text-info me-2"></i>
                                            Passo 1: Selecionar Aluno
                                        </h4>

                                        <div class="form-group mb-3">
                                            <label for="aluno_id">Aluno *</label>
                                            <select class="form-control" id="aluno_id" name="aluno_id" required>
                                                <option value="">Selecione um aluno...</option>
                                                <?php foreach ($alunos as $aluno): ?>
                                                    <option value="<?php echo $aluno['id']; ?>" 
                                                            <?php echo (isset($_GET['aluno_id']) && (int)$_GET['aluno_id'] === (int)$aluno['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($aluno['nome']); ?>
                                                        <?php if ($aluno['turma_nome']): ?>
                                                            - <?php echo htmlspecialchars($aluno['turma_nome']); ?>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <?php if ($aluno_selecionado): ?>
                                            <div class="resumo-box">
                                                <h5 class="mb-3">Informações do Aluno</h5>
                                                
                                                <div class="info-line">
                                                    <span class="info-label">Aluno:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($aluno_selecionado['nome']); ?></span>
                                                </div>
                                                
                                                <div class="info-line">
                                                    <span class="info-label">Responsável:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($aluno_selecionado['nome_resp_legal'] ?? '-'); ?></span>
                                                </div>
                                                
                                                <div class="info-line">
                                                    <span class="info-label">CPF Responsável:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($aluno_selecionado['cpf_resp_legal'] ?? '-'); ?></span>
                                                </div>
                                                
                                                <?php if ($aluno_selecionado['turma_nome']): ?>
                                                <div class="info-line">
                                                    <span class="info-label">Turma:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($aluno_selecionado['turma_nome']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- PASSO 2: DADOS DO RECIBO -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">
                                            <i class="mdi mdi-receipt text-primary me-2"></i>
                                            Passo 2: Dados do Recibo
                                        </h4>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="tipo">Tipo de Recibo *</label>
                                                    <select class="form-control" id="tipo" name="tipo" required>
                                                        <option value="">Selecione...</option>
                                                        <option value="mensalidade">💰 Mensalidade</option>
                                                        <option value="fardamento">👔 Fardamento</option>
                                                        <option value="atividade">🎉 Atividade Escolar</option>
                                                        <option value="matricula">📝 Matrícula</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="referencia">Referência</label>
                                                    <input type="text" class="form-control" id="referencia" name="referencia" 
                                                           placeholder="Ex: 2024-01, Uniforme Completo, etc">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="usar_itens" name="usar_itens" value="1" onchange="toggleItens()">
                                                <label class="form-check-label" for="usar_itens">
                                                    <strong>Usar itens detalhados</strong>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Formulário Simples (sem itens) -->
                                        <div id="form_simples">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group mb-3">
                                                        <label for="valor_original">Valor Original (R$) *</label>
                                                        <input type="number" class="form-control" id="valor_original" name="valor_original" 
                                                               step="0.01" min="0" required onchange="calcularValorFinal()">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Formulário com Itens -->
                                        <div id="form_itens" style="display: none;">
                                            <div class="alert alert-info mb-3">
                                                <i class="mdi mdi-information"></i> Adicione os itens abaixo. O valor será calculado automaticamente.
                                            </div>
                                            
                                            <div id="itens_container">
                                                <!-- Itens serão adicionados aqui via JavaScript -->
                                            </div>
                                            
                                            <div class="text-end mb-3">
                                                <button type="button" class="btn btn-outline-primary" onclick="adicionarItem()">
                                                    <i class="mdi mdi-plus"></i> Adicionar Item
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Campos Compartilhados (Desconto, Acréscimos, Valor Final) -->
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    <label for="desconto">Desconto (R$)</label>
                                                    <input type="number" class="form-control" id="desconto" name="desconto" 
                                                           step="0.01" min="0" value="0" onchange="calcularValorFinal()">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    <label for="acrescimos">Acréscimos (R$)</label>
                                                    <input type="number" class="form-control" id="acrescimos" name="acrescimos" 
                                                           step="0.01" min="0" value="0" onchange="calcularValorFinal()">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    <label for="valor_final">Valor Final (R$)</label>
                                                    <input type="text" class="form-control" id="valor_final" readonly 
                                                           style="background-color: #f0f0f0; font-weight: bold; font-size: 1.2em;" value="0,00">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    <label for="vencimento">Data de Vencimento</label>
                                                    <input type="date" class="form-control" id="vencimento" name="vencimento">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="descricao">Descrição</label>
                                            <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="observacoes">Observações (Internas)</label>
                                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BOTÕES DE AÇÃO -->
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="gerar_recibo" class="btn btn-gradient-primary btn-lg flex-fill">
                                                <i class="mdi mdi-check-circle me-2"></i>
                                                Gerar Recibo
                                            </button>
                                            <a href="index.php" class="btn btn-light btn-lg">
                                                <i class="mdi mdi-arrow-left me-2"></i>
                                                Voltar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php include __DIR__ . '/../partials/_footer.php'; ?>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>

    <script>
        // Atualizar dados do aluno ao selecionar
        document.getElementById('aluno_id').addEventListener('change', function() {
            if (this.value) {
                window.location.href = 'gerar.php?aluno_id=' + this.value;
            }
        });

        // Toggle entre formulário simples e itens
        function toggleItens() {
            const usarItens = document.getElementById('usar_itens').checked;
            const formSimples = document.getElementById('form_simples');
            const formItens = document.getElementById('form_itens');
            const valorOriginal = document.getElementById('valor_original');
            
            if (usarItens) {
                formSimples.style.display = 'none';
                formItens.style.display = 'block';
                valorOriginal.removeAttribute('required');
                
                // Adicionar primeiro item se não houver
                if (document.querySelectorAll('.item-row').length === 0) {
                    adicionarItem();
                }
            } else {
                formSimples.style.display = 'block';
                formItens.style.display = 'none';
                valorOriginal.setAttribute('required', 'required');
            }
            calcularValorFinal();
        }

        // Adicionar item
        let itemCounter = 0;
        function adicionarItem() {
            const container = document.getElementById('itens_container');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-3 p-3 border rounded';
            newItem.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Descrição *</label>
                        <input type="text" class="form-control" name="itens[${itemCounter}][descricao]" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantidade</label>
                        <input type="number" class="form-control quantidade" step="0.01" min="0.01" value="1" onchange="calcularValorFinal()" name="itens[${itemCounter}][quantidade]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unidade</label>
                        <input type="text" class="form-control" placeholder="un" name="itens[${itemCounter}][unidade]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Valor Unit. *</label>
                        <input type="number" class="form-control valor_unitario" step="0.01" min="0" onchange="calcularValorFinal()" required name="itens[${itemCounter}][valor_unitario]">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-block" onclick="removerItem(this)">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            itemCounter++;
        }

        // Remover item
        function removerItem(btn) {
            btn.closest('.item-row').remove();
            calcularValorFinal();
        }

        // Calcular valor final
        function calcularValorFinal() {
            const usarItens = document.getElementById('usar_itens').checked;
            let valorOriginal = 0;
            const desconto = parseFloat(document.getElementById('desconto').value) || 0;
            const acrescimos = parseFloat(document.getElementById('acrescimos').value) || 0;
            
            if (usarItens) {
                // Calcular a partir dos itens
                const itens = document.querySelectorAll('.item-row');
                itens.forEach(item => {
                    const quantidade = parseFloat(item.querySelector('.quantidade').value) || 0;
                    const valorUnitario = parseFloat(item.querySelector('.valor_unitario').value) || 0;
                    valorOriginal += quantidade * valorUnitario;
                });
            } else {
                valorOriginal = parseFloat(document.getElementById('valor_original').value) || 0;
            }
            
            const valorFinal = valorOriginal - desconto + acrescimos;
            document.getElementById('valor_final').value = valorFinal.toFixed(2).replace('.', ',');
        }
    </script>
</body>
</html>
