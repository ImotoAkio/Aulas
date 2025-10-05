<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
}

$erro = '';
$sucesso = '';

// Gerar mensalidades para o ano todo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar_ano') {
  $ano = (int)($_POST['ano'] ?? 0);
  $valor_padrao = (float)($_POST['valor_padrao_ano'] ?? 0);
  $vencimento_dia = (int)($_POST['vencimento_dia'] ?? 10);
  $tipo_geracao = $_POST['tipo_geracao'] ?? ''; // 'aluno' ou 'turma'
  $aluno_id = $tipo_geracao === 'aluno' ? (int)($_POST['aluno_id_ano'] ?? 0) : 0;
  $turma_id = $tipo_geracao === 'turma' ? (int)($_POST['turma_id_ano'] ?? 0) : 0;

  if ($ano < 2020 || $ano > 2030 || $valor_padrao <= 0 || $vencimento_dia < 1 || $vencimento_dia > 31) {
    $erro = 'Informe ano válido (2020-2030), valor padrão e dia de vencimento (1-31).';
  } elseif ($tipo_geracao === '' || ($tipo_geracao === 'aluno' && $aluno_id <= 0) || ($tipo_geracao === 'turma' && $turma_id <= 0)) {
    $erro = 'Selecione o tipo de geração e aluno/turma.';
  } else {
    try {
      $pdo->beginTransaction();
      
      // Buscar alunos baseado no tipo
      if ($tipo_geracao === 'aluno') {
        $stmtAlunos = $pdo->prepare("SELECT id FROM alunos WHERE id = :a");
        $stmtAlunos->execute([':a' => $aluno_id]);
      } else {
        $stmtAlunos = $pdo->prepare("SELECT id FROM alunos WHERE turma_id = :t");
        $stmtAlunos->execute([':t' => $turma_id]);
      }
      $alunos = $stmtAlunos->fetchAll(PDO::FETCH_COLUMN);
      
      if (empty($alunos)) {
        $erro = 'Nenhum aluno encontrado para os critérios informados.';
      } else {
        // Gerar mensalidades para todos os meses do ano
        $stmtCheck = $pdo->prepare("SELECT 1 FROM mensalidades WHERE aluno_id = :a AND competencia = :c");
        $stmtIns = $pdo->prepare(
          "INSERT INTO mensalidades (aluno_id, competencia, valor_original, desconto, acrescimos, valor_final, vencimento, status)
           VALUES (:a, :c, :vo, 0, 0, :vf, :venc, 'gerada')"
        );
        
        $qtInseridos = 0;
        $meses = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        
        foreach ($alunos as $aluno_id_atual) {
          foreach ($meses as $mes) {
            $competencia = $ano . '-' . $mes;
            $vencimento = $ano . '-' . $mes . '-' . str_pad($vencimento_dia, 2, '0', STR_PAD_LEFT);
            
            // Verificar se já existe
            $stmtCheck->execute([':a' => $aluno_id_atual, ':c' => $competencia]);
            if (!$stmtCheck->fetch()) {
              $stmtIns->execute([
                ':a' => $aluno_id_atual,
                ':c' => $competencia,
                ':vo' => $valor_padrao,
                ':vf' => $valor_padrao,
                ':venc' => $vencimento
              ]);
              $qtInseridos++;
            }
          }
        }
        
        $pdo->commit();
        $sucesso = "Mensalidades geradas para o ano $ano: $qtInseridos registros";
      }
    } catch (Throwable $e) {
      $pdo->rollBack();
      error_log('Erro gerar mensalidades ano: ' . $e->getMessage());
      $erro = 'Falha ao gerar mensalidades para o ano.';
    }
  }
}

// Gerar mensalidade individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar_individual') {
  $aluno_id = (int)($_POST['aluno_id'] ?? 0);
  $competencia = trim($_POST['competencia_individual'] ?? '');
  $valor_original = (float)($_POST['valor_original'] ?? 0);
  $desconto = (float)($_POST['desconto'] ?? 0);
  $acrescimos = (float)($_POST['acrescimos'] ?? 0);
  $vencimento = $_POST['vencimento_individual'] ?? '';
  $observacoes = trim($_POST['observacoes'] ?? '');

  if ($aluno_id <= 0 || $competencia === '' || $valor_original <= 0 || $vencimento === '') {
    $erro = 'Informe aluno, competência, valor original e vencimento.';
  } else {
    $valor_final = $valor_original - $desconto + $acrescimos;
    if ($valor_final < 0) {
      $erro = 'Valor final não pode ser negativo.';
    } else {
      try {
        // Verificar se já existe mensalidade para este aluno/competência
        $stmtCheck = $pdo->prepare("SELECT 1 FROM mensalidades WHERE aluno_id = :a AND competencia = :c");
        $stmtCheck->execute([':a' => $aluno_id, ':c' => $competencia]);
        
        if ($stmtCheck->fetch()) {
          $erro = 'Já existe mensalidade para este aluno na competência informada.';
        } else {
          $stmtIns = $pdo->prepare(
            "INSERT INTO mensalidades (aluno_id, competencia, valor_original, desconto, acrescimos, valor_final, vencimento, observacoes, status)
             VALUES (:a, :c, :vo, :desc, :acr, :vf, :venc, :obs, 'gerada')"
          );
          $stmtIns->execute([
            ':a' => $aluno_id,
            ':c' => $competencia,
            ':vo' => $valor_original,
            ':desc' => $desconto,
            ':acr' => $acrescimos,
            ':vf' => $valor_final,
            ':venc' => $vencimento,
            ':obs' => $observacoes
          ]);
          $sucesso = 'Mensalidade individual gerada com sucesso!';
        }
      } catch (Throwable $e) {
        error_log('Erro gerar mensalidade individual: ' . $e->getMessage());
        $erro = 'Falha ao gerar mensalidade individual.';
      }
    }
  }
}

// Gerar mensalidades em lote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar') {
  $competencia = trim($_POST['competencia'] ?? ''); // YYYY-MM
  $valor_padrao = (float)($_POST['valor_padrao'] ?? 0);
  $vencimento = $_POST['vencimento'] ?? '';
  $turma_id = $_POST['turma_id'] !== '' ? (int)$_POST['turma_id'] : null;

  if ($competencia === '' || $valor_padrao <= 0 || $vencimento === '') {
    $erro = 'Informe competência, valor padrão e vencimento.';
  } else {
    try {
      $pdo->beginTransaction();
      // Buscar alunos (por turma se informada)
      if ($turma_id) {
        $stmtAlunos = $pdo->prepare("SELECT id FROM alunos WHERE turma_id = :t");
        $stmtAlunos->execute([':t' => $turma_id]);
      } else {
        $stmtAlunos = $pdo->query("SELECT id FROM alunos");
      }
      $alunos = $stmtAlunos->fetchAll(PDO::FETCH_COLUMN);

      // Inserir se não existir mensalidade do mês
      $stmtCheck = $pdo->prepare("SELECT 1 FROM mensalidades WHERE aluno_id = :a AND competencia = :c");
      $stmtIns = $pdo->prepare(
        "INSERT INTO mensalidades (aluno_id, competencia, valor_original, desconto, acrescimos, valor_final, vencimento, status)
         VALUES (:a, :c, :vo, 0, 0, :vf, :venc, 'gerada')"
      );

      $qtInseridos = 0;
      foreach ($alunos as $aluno_id) {
        $stmtCheck->execute([':a' => $aluno_id, ':c' => $competencia]);
        if (!$stmtCheck->fetch()) {
          $stmtIns->execute([
            ':a' => $aluno_id,
            ':c' => $competencia,
            ':vo' => $valor_padrao,
            ':vf' => $valor_padrao,
            ':venc' => $vencimento
          ]);
          $qtInseridos++;
        }
      }
      $pdo->commit();
      $sucesso = "Mensalidades geradas: $qtInseridos";
    } catch (Throwable $e) {
      $pdo->rollBack();
      error_log('Erro gerar mensalidades: ' . $e->getMessage());
      $erro = 'Falha ao gerar mensalidades.';
    }
  }
}

// Carregar turmas
$turmas = [];
try { $turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Gerar Mensalidades</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/ti-icons/css/themify-icons.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>">
</head>
<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Gerar Mensalidades</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gerar Mensalidades</li>
              </ol>
            </nav>
          </div>

          <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
          <?php if ($sucesso): ?><div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div><?php endif; ?>

          <!-- 1. GERAÇÃO EM MASSA -->
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">
                <i class="mdi mdi-account-multiple text-primary me-2"></i>
                Geração em Massa
              </h4>
              <p class="text-muted">Gera mensalidades com valor padrão para uma turma específica em um mês.</p>
              <form class="row g-3" method="post" action="mensalidades.php">
                <input type="hidden" name="acao" value="gerar">
                <div class="col-md-3">
                  <label class="form-label">Competência</label>
                  <input type="month" name="competencia" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Vencimento</label>
                  <input type="date" name="vencimento" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor Padrão (R$)</label>
                  <input type="number" name="valor_padrao" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Turma (opcional)</label>
                  <select name="turma_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($turmas as $t): ?>
                      <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <button class="btn btn-gradient-primary" type="submit">Gerar em Massa</button>
                </div>
              </form>
            </div>
          </div>

          <!-- 2. GERAÇÃO ANUAL -->
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">
                <i class="mdi mdi-calendar-range text-warning me-2"></i>
                Geração Anual
              </h4>
              <p class="text-muted">Gera mensalidades para todos os 12 meses do ano para um aluno ou turma.</p>
              <form class="row g-3" method="post" action="mensalidades.php">
                <input type="hidden" name="acao" value="gerar_ano">
                <div class="col-md-3">
                  <label class="form-label">Ano</label>
                  <select name="ano" class="form-control" required>
                    <option value="">Selecione o ano</option>
                    <?php for ($i = 2024; $i <= 2026; $i++): ?>
                      <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor Padrão (R$)</label>
                  <input type="number" name="valor_padrao_ano" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Dia do Vencimento</label>
                  <select name="vencimento_dia" class="form-control" required>
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                      <option value="<?php echo $i; ?>" <?php echo $i === 10 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Tipo de Geração</label>
                  <select name="tipo_geracao" class="form-control" required onchange="toggleAnoFields()">
                    <option value="">Selecione</option>
                    <option value="aluno">Para um aluno específico</option>
                    <option value="turma">Para uma turma inteira</option>
                  </select>
                </div>
                
                <div class="col-md-6" id="aluno-field" style="display: none;">
                  <label class="form-label">Aluno</label>
                  <select name="aluno_id_ano" class="form-control">
                    <option value="">Selecione um aluno</option>
                    <?php 
                    try {
                      $stmtAlunos = $pdo->query("SELECT a.id, a.nome, t.nome as turma_nome FROM alunos a LEFT JOIN turmas t ON t.id = a.turma_id ORDER BY a.nome");
                      $alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
                      foreach ($alunos as $aluno): 
                    ?>
                      <option value="<?php echo (int)$aluno['id']; ?>">
                        <?php echo htmlspecialchars($aluno['nome']); ?>
                        <?php if ($aluno['turma_nome']): ?>
                          (<?php echo htmlspecialchars($aluno['turma_nome']); ?>)
                        <?php endif; ?>
                      </option>
                    <?php 
                      endforeach;
                    } catch (Throwable $e) {}
                    ?>
                  </select>
                </div>
                
                <div class="col-md-6" id="turma-field" style="display: none;">
                  <label class="form-label">Turma</label>
                  <select name="turma_id_ano" class="form-control">
                    <option value="">Selecione uma turma</option>
                    <?php foreach ($turmas as $t): ?>
                      <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-12">
                  <div class="alert alert-info">
                    <strong>⚠️ Atenção:</strong> Esta operação irá gerar mensalidades para todos os 12 meses do ano selecionado. 
                    Mensalidades já existentes serão ignoradas.
                  </div>
                </div>
                
                <div class="col-12">
                  <button class="btn btn-gradient-warning" type="submit">Gerar Mensalidades para o Ano Todo</button>
                </div>
              </form>
            </div>
          </div>

          <!-- 3. GERAÇÃO INDIVIDUAL -->
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">
                <i class="mdi mdi-account text-success me-2"></i>
                Geração Individual
              </h4>
              <p class="text-muted">Gera mensalidade específica com valor personalizado para um aluno.</p>
              <form class="row g-3" method="post" action="mensalidades.php">
                <input type="hidden" name="acao" value="gerar_individual">
                <div class="col-md-4">
                  <label class="form-label">Aluno</label>
                  <select name="aluno_id" class="form-control" required>
                    <option value="">Selecione um aluno</option>
                    <?php 
                    try {
                      $stmtAlunos = $pdo->query("SELECT a.id, a.nome, t.nome as turma_nome FROM alunos a LEFT JOIN turmas t ON t.id = a.turma_id ORDER BY a.nome");
                      $alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
                      foreach ($alunos as $aluno): 
                    ?>
                      <option value="<?php echo (int)$aluno['id']; ?>">
                        <?php echo htmlspecialchars($aluno['nome']); ?>
                        <?php if ($aluno['turma_nome']): ?>
                          (<?php echo htmlspecialchars($aluno['turma_nome']); ?>)
                        <?php endif; ?>
                      </option>
                    <?php 
                      endforeach;
                    } catch (Throwable $e) {}
                    ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Competência</label>
                  <input type="month" name="competencia_individual" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Vencimento</label>
                  <input type="date" name="vencimento_individual" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor Original (R$)</label>
                  <input type="number" name="valor_original" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Desconto (R$)</label>
                  <input type="number" name="desconto" step="0.01" min="0" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Acréscimos (R$)</label>
                  <input type="number" name="acrescimos" step="0.01" min="0" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor Final (R$)</label>
                  <input type="text" class="form-control" readonly style="background-color: #f8f9fa;">
                  <small class="text-muted">Calculado automaticamente</small>
                </div>
                <div class="col-12">
                  <label class="form-label">Observações</label>
                  <textarea name="observacoes" class="form-control" rows="2" placeholder="Ex: Bolsa de 50%, desconto por pagamento antecipado, etc."></textarea>
                </div>
                <div class="col-12">
                  <button class="btn btn-gradient-success" type="submit">Gerar Mensalidade Individual</button>
                </div>
              </form>
            </div>
          </div>

          <!-- LINK PARA LISTAGEM -->
          <div class="card">
            <div class="card-body text-center">
              <h5 class="card-title">
                <i class="mdi mdi-format-list-bulleted text-info me-2"></i>
                Visualizar Mensalidades Geradas
              </h5>
              <p class="card-text">Acesse a listagem para visualizar, filtrar e gerenciar as mensalidades.</p>
              <a href="<?php echo getPageUrl('financeiro/listar_mensalidades.php'); ?>" class="btn btn-gradient-info">
                <i class="mdi mdi-eye me-2"></i>Ver Listagem
              </a>
            </div>
          </div>

        </div>
        <?php include __DIR__ . '/partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/jquery.cookie.js'); ?>"></script>
  
  <script>
    // Calcular valor final automaticamente
    function calcularValorFinal() {
      const valorOriginal = parseFloat(document.querySelector('input[name="valor_original"]').value) || 0;
      const desconto = parseFloat(document.querySelector('input[name="desconto"]').value) || 0;
      const acrescimos = parseFloat(document.querySelector('input[name="acrescimos"]').value) || 0;
      
      const valorFinal = valorOriginal - desconto + acrescimos;
      const campoValorFinal = document.querySelector('input[name="valor_original"]').closest('.row').querySelector('input[readonly]');
      
      if (campoValorFinal) {
        campoValorFinal.value = valorFinal.toFixed(2).replace('.', ',');
      }
    }
    
    // Controlar exibição dos campos para geração anual
    function toggleAnoFields() {
      const tipoGeracao = document.querySelector('select[name="tipo_geracao"]').value;
      const alunoField = document.getElementById('aluno-field');
      const turmaField = document.getElementById('turma-field');
      
      if (tipoGeracao === 'aluno') {
        alunoField.style.display = 'block';
        turmaField.style.display = 'none';
        document.querySelector('select[name="aluno_id_ano"]').required = true;
        document.querySelector('select[name="turma_id_ano"]').required = false;
      } else if (tipoGeracao === 'turma') {
        alunoField.style.display = 'none';
        turmaField.style.display = 'block';
        document.querySelector('select[name="aluno_id_ano"]').required = false;
        document.querySelector('select[name="turma_id_ano"]').required = true;
      } else {
        alunoField.style.display = 'none';
        turmaField.style.display = 'none';
        document.querySelector('select[name="aluno_id_ano"]').required = false;
        document.querySelector('select[name="turma_id_ano"]').required = false;
      }
    }
    
    // Adicionar event listeners
    document.addEventListener('DOMContentLoaded', function() {
      const campos = ['valor_original', 'desconto', 'acrescimos'];
      campos.forEach(function(campo) {
        const input = document.querySelector('input[name="' + campo + '"]');
        if (input) {
          input.addEventListener('input', calcularValorFinal);
        }
      });
      
      // Calcular inicialmente
      calcularValorFinal();
      
      // Inicializar campos de geração anual
      toggleAnoFields();
    });
  </script>
</body>
</html>