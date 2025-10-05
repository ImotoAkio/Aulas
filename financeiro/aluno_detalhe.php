<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$aluno = null;
$turma = null;
$historico_pagamentos = [];
$mensalidades = [];
$notas = [];
$pareceres = [];

try {
  // Buscar dados completos do aluno
  $stmt = $pdo->prepare("
    SELECT a.*, t.nome as turma_nome, t.ano_letivo 
    FROM alunos a 
    LEFT JOIN turmas t ON t.id = a.turma_id 
    WHERE a.id = :id
  ");
  $stmt->execute([':id' => $id]);
  $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($aluno) {
    // Buscar histórico de pagamentos
    $stmt2 = $pdo->prepare("
      SELECT referencia_mes, valor, status, meio, pago_em, observacoes 
      FROM pagamentos 
      WHERE aluno_id = :id 
      ORDER BY referencia_mes DESC 
      LIMIT 12
    ");
    $stmt2->execute([':id' => $id]);
    $historico_pagamentos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Buscar mensalidades
    $stmt3 = $pdo->prepare("
      SELECT competencia, valor_original, desconto, acrescimos, valor_final, vencimento, status, observacoes 
      FROM mensalidades 
      WHERE aluno_id = :id 
      ORDER BY vencimento DESC 
      LIMIT 12
    ");
    $stmt3->execute([':id' => $id]);
    $mensalidades = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // Buscar notas do aluno
    $stmt4 = $pdo->prepare("
      SELECT n.unidade, n.nota_1, n.nota_2, n.media, d.nome as disciplina_nome
      FROM notas n
      JOIN disciplinas d ON d.id = n.disciplina_id
      WHERE n.aluno_id = :id
      ORDER BY n.unidade DESC, d.nome
    ");
    $stmt4->execute([':id' => $id]);
    $notas = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    // Buscar pareceres
    $stmt5 = $pdo->prepare("
      SELECT p.unidade, p.parecer, p.data_parecer, u.nome as professor_nome
      FROM pareceres p
      JOIN usuarios u ON u.id = p.professor_id
      WHERE p.aluno_id = :id
      ORDER BY p.unidade DESC, p.data_parecer DESC
    ");
    $stmt5->execute([':id' => $id]);
    $pareceres = $stmt5->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  error_log('Erro ao buscar dados do aluno: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Detalhe do Aluno</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
</head>
<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Detalhes do Aluno</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/alunos.php'); ?>">Alunos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
              </ol>
            </nav>
          </div>

          <?php if (!$aluno): ?>
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle me-2"></i>
              Aluno não encontrado.
            </div>
          <?php else: ?>

            <!-- INFORMAÇÕES PESSOAIS -->
            <div class="row mb-4">
              <div class="col-md-8">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <h4 class="card-title">
                        <i class="mdi mdi-account text-primary me-2"></i>
                        Informações Pessoais
                      </h4>
                      <span class="badge badge-info">ID: <?php echo $aluno['id']; ?></span>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6">
                        <h6 class="text-muted">Nome Completo</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']); ?></p>
                        
                        <h6 class="text-muted">Data de Nascimento</h6>
                        <p class="mb-3"><?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?></p>
                        
                        <h6 class="text-muted">Sexo</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['sexo'] ?: '-'); ?></p>
                        
                        <h6 class="text-muted">CPF</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['cpf'] ?: '-'); ?></p>
                        
                        <h6 class="text-muted">RG</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['rg'] ?: '-'); ?></p>
                      </div>
                      
                      <div class="col-md-6">
                        <h6 class="text-muted">Nacionalidade</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['nacionalidade'] ?: '-'); ?></p>
                        
                        <h6 class="text-muted">Naturalidade</h6>
                        <p class="mb-3">
                          <?php 
                          $naturalidade = [];
                          if ($aluno['naturalidade_cidade']) $naturalidade[] = $aluno['naturalidade_cidade'];
                          if ($aluno['naturalidade_estado']) $naturalidade[] = $aluno['naturalidade_estado'];
                          echo htmlspecialchars(implode(' - ', $naturalidade) ?: '-');
                          ?>
                        </p>
                        
                        <h6 class="text-muted">Tipo Sanguíneo</h6>
                        <p class="mb-3">
                          <?php 
                          $tipo_sang = [];
                          if ($aluno['tipo_sanguineo']) $tipo_sang[] = $aluno['tipo_sanguineo'];
                          if ($aluno['fator_rh']) $tipo_sang[] = $aluno['fator_rh'];
                          echo htmlspecialchars(implode('', $tipo_sang) ?: '-');
                          ?>
                        </p>
                        
                        <h6 class="text-muted">NIS</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['nis'] ?: '-'); ?></p>
                        
                        <h6 class="text-muted">Alergias</h6>
                        <p class="mb-3"><?php echo htmlspecialchars($aluno['alergias'] ?: 'Nenhuma informada'); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-school text-success me-2"></i>
                      Informações Acadêmicas
                    </h4>
                    
                    <h6 class="text-muted">Nome</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['nome']); ?></p>
                    
                    <h6 class="text-muted">Turma</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['turma_nome'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">Ano Letivo</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['ano_letivo'] ?: '-'); ?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- INFORMAÇÕES DE CONTATO -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-map-marker text-warning me-2"></i>
                      Endereço
                    </h4>
                    
                    <h6 class="text-muted">Endereço Completo</h6>
                    <p class="mb-3">
                      <?php 
                      $endereco = [];
                      if ($aluno['endereco']) $endereco[] = $aluno['endereco'];
                      if ($aluno['numero']) $endereco[] = $aluno['numero'];
                      if ($aluno['complemento']) $endereco[] = $aluno['complemento'];
                      echo htmlspecialchars(implode(', ', $endereco) ?: '-');
                      ?>
                    </p>
                    
                    <h6 class="text-muted">Bairro</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['bairro'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">CEP</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['cep'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">Cidade/Estado</h6>
                    <p class="mb-3">
                      <?php 
                      $cidade_estado = [];
                      if ($aluno['cidade']) $cidade_estado[] = $aluno['cidade'];
                      if ($aluno['estado']) $cidade_estado[] = $aluno['estado'];
                      echo htmlspecialchars(implode(' - ', $cidade_estado) ?: '-');
                      ?>
                    </p>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-phone text-info me-2"></i>
                      Contatos
                    </h4>
                    
                    <h6 class="text-muted">Telefone 1</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['telefone1'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">Telefone 2</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['telefone2'] ?: '-'); ?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- RESPONSÁVEIS -->
            <div class="row mb-4">
              <div class="col-md-4">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-account-female text-pink me-2"></i>
                      Mãe
                    </h4>
                    
                    <h6 class="text-muted">Nome</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['nome_mae'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">CPF</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['cpf_mae'] ?: '-'); ?></p>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-account-male text-blue me-2"></i>
                      Pai
                    </h4>
                    
                    <h6 class="text-muted">Nome</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['nome_pai'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">CPF</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['cpf_pai'] ?: '-'); ?></p>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-account-supervisor text-purple me-2"></i>
                      Responsável Legal
                    </h4>
                    
                    <h6 class="text-muted">Nome</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['nome_resp_legal'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">CPF</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['cpf_resp_legal'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">Profissão</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['profissao_resp_legal'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">Parentesco</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['grau_parentesco_resp_legal'] ?: '-'); ?></p>
                    
                    <h6 class="text-muted">Local de Trabalho</h6>
                    <p class="mb-3"><?php echo htmlspecialchars($aluno['local_trabalho_resp_legal'] ?: '-'); ?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- INFORMAÇÕES FINANCEIRAS -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-cash-multiple text-success me-2"></i>
                      Histórico de Pagamentos
                    </h4>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Mês</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Meio</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($historico_pagamentos as $h): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($h['referencia_mes']); ?></td>
                              <td>R$ <?php echo number_format((float)$h['valor'], 2, ',', '.'); ?></td>
                              <td>
                                <span class="badge badge-<?php 
                                  echo match($h['status']) {
                                    'pago' => 'success',
                                    'pendente' => 'warning', 
                                    'atrasado' => 'danger',
                                    default => 'info'
                                  };
                                ?>">
                                  <?php echo htmlspecialchars($h['status']); ?>
                                </span>
                              </td>
                              <td><?php echo htmlspecialchars($h['meio'] ?: '-'); ?></td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (!$historico_pagamentos): ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum pagamento registrado.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-calendar-check text-warning me-2"></i>
                      Mensalidades
                    </h4>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Competência</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($mensalidades as $m): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($m['competencia']); ?></td>
                              <td><?php echo htmlspecialchars($m['vencimento']); ?></td>
                              <td>R$ <?php echo number_format((float)$m['valor_final'], 2, ',', '.'); ?></td>
                              <td>
                                <span class="badge badge-<?php 
                                  echo match($m['status']) {
                                    'paga' => 'success',
                                    'pendente' => 'warning', 
                                    'atrasada' => 'danger',
                                    'cancelada' => 'secondary',
                                    default => 'info'
                                  };
                                ?>">
                                  <?php echo htmlspecialchars($m['status']); ?>
                                </span>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (!$mensalidades): ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhuma mensalidade registrada.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- INFORMAÇÕES ACADÊMICAS -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-school text-primary me-2"></i>
                      Notas
                    </h4>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Disciplina</th>
                            <th>Unidade</th>
                            <th>Nota 1</th>
                            <th>Nota 2</th>
                            <th>Média</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($notas as $n): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($n['disciplina_nome']); ?></td>
                              <td><?php echo htmlspecialchars($n['unidade']); ?></td>
                              <td><?php echo number_format((float)$n['nota_1'], 1, ',', '.'); ?></td>
                              <td><?php echo number_format((float)$n['nota_2'], 1, ',', '.'); ?></td>
                              <td><strong><?php echo number_format((float)$n['media'], 1, ',', '.'); ?></strong></td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (!$notas): ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhuma nota registrada.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-file-document text-info me-2"></i>
                      Pareceres
                    </h4>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Unidade</th>
                            <th>Professor</th>
                            <th>Data</th>
                            <th>Parecer</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($pareceres as $p): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($p['unidade']); ?></td>
                              <td><?php echo htmlspecialchars($p['professor_nome']); ?></td>
                              <td><?php echo $p['data_parecer'] ? date('d/m/Y', strtotime($p['data_parecer'])) : '-'; ?></td>
                              <td>
                                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($p['parecer']); ?>">
                                  <?php echo htmlspecialchars($p['parecer']); ?>
                                </span>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (!$pareceres): ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum parecer registrado.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- AÇÕES -->
            <div class="card">
              <div class="card-body text-center">
                <h5 class="card-title">
                  <i class="mdi mdi-cog text-secondary me-2"></i>
                  Ações Disponíveis
                </h5>
                <div class="btn-group" role="group">
                  <a href="<?php echo getPageUrl('financeiro/mensalidades.php'); ?>" class="btn btn-gradient-primary">
                    <i class="mdi mdi-plus me-2"></i>Gerar Mensalidade
                  </a>
                  <a href="<?php echo getPageUrl('financeiro/pagamentos.php'); ?>" class="btn btn-gradient-success">
                    <i class="mdi mdi-cash me-2"></i>Registrar Pagamento
                  </a>
                  <a href="<?php echo getPageUrl('financeiro/alunos.php'); ?>" class="btn btn-gradient-info">
                    <i class="mdi mdi-arrow-left me-2"></i>Voltar para Lista
                  </a>
                </div>
              </div>
            </div>

          <?php endif; ?>

        </div>
        <?php include __DIR__ . '/partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
</body>
</html>