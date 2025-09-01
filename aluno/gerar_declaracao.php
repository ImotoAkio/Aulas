<?php
session_start();
include('../secretaria/partials/db.php');

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    header('Location: ../../login.php');
    exit();
}

// Verificar se há dados da declaração
if (!isset($_SESSION['declaracao_aluno'])) {
    header('Location: declaracoes.php');
    exit();
}

$dados = $_SESSION['declaracao_aluno'];
$aluno = $dados['aluno'];
$tipo = $dados['tipo'];
$data_emissao = $dados['data_emissao'];
$finalidade = $dados['finalidade'];

// Limpar dados da sessão após uso
unset($_SESSION['declaracao_aluno']);

// Formatar data
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$data_formatada = date('d/m/Y', strtotime($data_emissao));
$data_extenso = strftime('%d de %B de %Y', strtotime($data_emissao));

// Definir título da declaração baseado no tipo
$titulos = [
    'vinculo' => 'DECLARAÇÃO DE VÍNCULO ESCOLAR',
    'matricula' => 'DECLARAÇÃO DE MATRÍCULA',
    'frequencia' => 'DECLARAÇÃO DE FREQUÊNCIA ESCOLAR',
    'transferencia' => 'DECLARAÇÃO PARA TRANSFERÊNCIA',
    'programa_social' => 'DECLARAÇÃO PARA PROGRAMA SOCIAL'
];

$titulo = $titulos[$tipo] ?? 'DECLARAÇÃO';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $titulo ?> - <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></title>
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { margin: 0; padding: 20px; }
      .declaracao-container { box-shadow: none !important; border: none !important; }
    }
    
    .declaracao-container {
      max-width: 800px;
      margin: 20px auto;
      background: white;
      padding: 40px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      border-radius: 10px;
      font-family: 'Times New Roman', serif;
      line-height: 1.6;
    }
    
    .cabecalho {
      text-align: center;
      margin-bottom: 40px;
      border-bottom: 2px solid #333;
      padding-bottom: 20px;
    }
    
    .logo {
      font-size: 24px;
      font-weight: bold;
      color: #333;
      margin-bottom: 10px;
    }
    
    .titulo {
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 10px;
    }
    
    .subtitulo {
      font-size: 14px;
      color: #666;
    }
    
    .conteudo {
      text-align: justify;
      margin-bottom: 40px;
      font-size: 14px;
    }
    
    .assinatura {
      margin-top: 60px;
      text-align: center;
    }
    
    .linha-assinatura {
      border-top: 1px solid #333;
      width: 200px;
      margin: 10px auto;
    }
    
    .carimbo {
      margin-top: 30px;
      text-align: center;
      font-size: 12px;
      color: #666;
    }
    
    .botoes {
      text-align: center;
      margin: 20px 0;
    }
    
    .btn-print, .btn-back {
      background: #007bff;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      margin: 0 10px;
    }
    
    .btn-back {
      background: #6c757d;
    }
    
    .btn-print:hover, .btn-back:hover {
      opacity: 0.8;
    }
  </style>
</head>

<body>
  <div class="no-print">
    <div class="botoes">
      <button class="btn-print" onclick="window.print()">
        <i class="mdi mdi-printer"></i> Imprimir Declaração
      </button>
      <button class="btn-back" onclick="window.location.href='declaracoes.php'">
        <i class="mdi mdi-arrow-left"></i> Voltar
      </button>
    </div>
  </div>

  <div class="declaracao-container">
    <!-- Cabeçalho -->
         <div class="cabecalho">
       <div class="logo">
         <img src="../assets/images/logo.png" alt="Logo" style="height: 60px; margin-bottom: 15px;">
         <div style="font-size: 24px; font-weight: bold; color: #333; margin-bottom: 10px;">COLÉGIO ROSA DE SHAROM</div>
       </div>
       <div class="titulo"><?= $titulo ?></div>
       <div class="subtitulo">Documento Oficial</div>
     </div>

    <!-- Conteúdo -->
    <div class="conteudo">
             <p>A <strong>COLÉGIO ROSA DE SHAROM</strong>, estabelecimento de ensino devidamente autorizado, declara que:</p>
      
      <p style="text-align: center; font-weight: bold; margin: 30px 0;">
        <strong><?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></strong>
      </p>
      
      <?php if ($tipo === 'vinculo'): ?>
        <p>está <strong>regularmente matriculado</strong> nesta instituição de ensino, cursando a <strong><?= htmlspecialchars($aluno['turma_nome'] ?? 'turma não definida') ?></strong> no ano letivo de <strong><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></strong>.</p>
      <?php elseif ($tipo === 'matricula'): ?>
        <p>está <strong>devidamente matriculado</strong> nesta instituição de ensino para o ano letivo de <strong><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></strong>, cursando a <strong><?= htmlspecialchars($aluno['turma_nome'] ?? 'turma não definida') ?></strong>.</p>
      <?php elseif ($tipo === 'frequencia'): ?>
        <p>está <strong>frequentando regularmente</strong> as aulas nesta instituição de ensino, cursando a <strong><?= htmlspecialchars($aluno['turma_nome'] ?? 'turma não definida') ?></strong> no ano letivo de <strong><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></strong>.</p>
      <?php elseif ($tipo === 'transferencia'): ?>
        <p>está <strong>regularmente matriculado</strong> nesta instituição de ensino, cursando a <strong><?= htmlspecialchars($aluno['turma_nome'] ?? 'turma não definida') ?></strong> no ano letivo de <strong><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></strong>, e solicita transferência para outra instituição de ensino.</p>
      <?php elseif ($tipo === 'programa_social'): ?>
        <p>está <strong>regularmente matriculado</strong> e <strong>frequentando</strong> esta instituição de ensino, cursando a <strong><?= htmlspecialchars($aluno['turma_nome'] ?? 'turma não definida') ?></strong> no ano letivo de <strong><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></strong>, para fins de inscrição em programa social.</p>
      <?php endif; ?>
      
      <?php if (!empty($finalidade)): ?>
        <p><strong>Finalidade:</strong> <?= htmlspecialchars($finalidade) ?></p>
      <?php endif; ?>
      
      <p>Esta declaração é válida para os fins a que se destina e é emitida em <strong><?= $data_extenso ?></strong>.</p>
      
      <p style="margin-top: 30px;">
        <strong>Local e Data:</strong> Recife, <?= $data_formatada ?>
      </p>
    </div>

    <!-- Assinatura -->
         <div class="assinatura">
       <div class="linha-assinatura"></div>
       <strong>COLÉGIO ROSA DE SHAROM</strong><br>
       <small>Secretaria Escolar</small>
     </div>

    <!-- Carimbo -->
    <div class="carimbo">
      <p>Documento gerado eletronicamente</p>
      <p>Data de emissão: <?= $data_formatada ?></p>
    </div>
  </div>

  <script>
    // Auto-print quando a página carrega (opcional)
    // window.onload = function() {
    //   window.print();
    // };
  </script>
</body>

</html>
