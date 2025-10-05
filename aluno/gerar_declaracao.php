<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// Verificar se há dados da declaração
if (!isset($_SESSION['declaracao_aluno'])) {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('aluno/declaracoes.php');
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
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
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
      padding: 50px;
      box-shadow: 0 0 25px rgba(0,0,0,0.15);
      border-radius: 15px;
      font-family: 'Times New Roman', serif;
      line-height: 1.8;
      border: 2px solid #f0f0f0;
    }
    
    .cabecalho {
      text-align: center;
      margin-bottom: 50px;
      border-bottom: 3px solid #2c3e50;
      padding-bottom: 30px;
      position: relative;
    }
    
    .cabecalho::before {
      content: '';
      position: absolute;
      top: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 4px;
      background: linear-gradient(90deg, #3498db, #2c3e50);
      border-radius: 2px;
    }
    
    .logo {
      font-size: 28px;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 15px;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }
    
    .logo-subtitle {
      font-size: 16px;
      color: #7f8c8d;
      font-style: italic;
      margin-bottom: 25px;
    }
    
    .endereco {
      font-size: 13px;
      color: #34495e;
      margin-bottom: 20px;
      line-height: 1.4;
    }
    
    .autorizacao {
      font-size: 12px;
      color: #7f8c8d;
      margin-bottom: 15px;
      line-height: 1.3;
    }
    
    .cnpj {
      font-size: 12px;
      color: #7f8c8d;
      margin-bottom: 25px;
      font-weight: 500;
    }
    
    .titulo {
      font-size: 22px;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 15px;
      color: #2c3e50;
      letter-spacing: 2px;
    }
    
    .subtitulo {
      font-size: 16px;
      color: #7f8c8d;
      font-weight: 500;
    }
    
    .conteudo {
      text-align: justify;
      margin-bottom: 50px;
      font-size: 16px;
      color: #34495e;
    }
    
    .conteudo p {
      margin-bottom: 20px;
      text-indent: 30px;
    }
    
    .nome-destacado {
      text-align: center;
      font-weight: bold;
      margin: 40px 0;
      padding: 20px;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-left: 5px solid #3498db;
      border-radius: 8px;
      font-size: 18px;
      color: #2c3e50;
    }
    
    .assinatura {
      margin-top: 80px;
      text-align: center;
    }
    
    .linha-assinatura {
      border-top: 2px solid #2c3e50;
      width: 250px;
      margin: 15px auto;
    }
    
    .assinatura-texto {
      font-weight: bold;
      color: #2c3e50;
      font-size: 16px;
    }
    
    .assinatura-subtexto {
      font-size: 14px;
      color: #7f8c8d;
      margin-top: 5px;
    }
    
    .carimbo {
      margin-top: 40px;
      text-align: center;
      font-size: 12px;
      color: #95a5a6;
      border-top: 1px solid #ecf0f1;
      padding-top: 20px;
    }
    
    .botoes {
      text-align: center;
      margin: 30px 0;
    }
    
    .btn-print, .btn-back {
      background: linear-gradient(135deg, #3498db, #2980b9);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 8px;
      cursor: pointer;
      margin: 0 15px;
      font-weight: 500;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    }
    
    .btn-back {
      background: linear-gradient(135deg, #95a5a6, #7f8c8d);
      box-shadow: 0 4px 8px rgba(149, 165, 166, 0.3);
    }
    
    .btn-print:hover, .btn-back:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    }
    
    .finalidade-box {
      background: #f8f9fa;
      border-left: 4px solid #3498db;
      padding: 15px 20px;
      margin: 20px 0;
      border-radius: 5px;
    }
    
    .data-local {
      text-align: right;
      margin-top: 40px;
      font-weight: 500;
      color: #2c3e50;
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
        Educandário Rosa de Sharom
      </div>
      <div class="logo-subtitle">
        Ensino Infantil, Fundamental e Médio
      </div>
      
      <div class="endereco">
        Avenida. 01, nº 86 Quati II, Petrolina – PE / CEP: 56314-510<br>
        Contato/Whatsapp: (87) 98837-5103 E-mail: rosasharom@gmail.com
      </div>
      
      <div class="autorizacao">
        Portaria de Autorização de Funcionamento nº 3.281 de 11/06/2004<br>
        Publicado no D.O nº 110 de 12/06/04<br>
        Cadastro escolar nº P- 653.118
      </div>
      
      <div class="cnpj">
        CNPJ: 49.000.772/0001-12 – Código do MEC/INEP Nº 26.168-294
      </div>
      
      <div class="titulo"><?= $titulo ?></div>
      <div class="subtitulo">Documento Oficial</div>
    </div>

    <!-- Conteúdo -->
    <div class="conteudo">
      <p>O <strong>EDUCANDÁRIO ROSA DE SHAROM</strong>, estabelecimento de ensino devidamente autorizado, declara que:</p>
      
      <div class="nome-destacado">
        <?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?>
      </div>
      
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
        <div class="finalidade-box">
          <p><strong>Finalidade:</strong> <?= htmlspecialchars($finalidade) ?></p>
        </div>
      <?php endif; ?>
      
      <p>Esta declaração é válida para os fins a que se destina e é emitida em <strong><?= $data_extenso ?></strong>.</p>
      
      <div class="data-local">
        <p><strong>Local e Data:</strong> Recife, <?= $data_formatada ?></p>
      </div>
    </div>

    <!-- Assinatura -->
    <div class="assinatura">
      <div class="linha-assinatura"></div>
      <div class="assinatura-texto">EDUCANDÁRIO ROSA DE SHAROM</div>
      <div class="assinatura-subtexto">Secretaria Escolar</div>
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
