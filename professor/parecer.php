<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * professor/parecer.php
 *
 * Esta página lista todos os pareceres pedagógicos que foram designados
 * ao professor logado pela secretaria/coordenação.
 * Permite ao professor acessar e preencher (ou editar) cada parecer.
 *
 * Utiliza a estrutura de template existente com partials.
 */

session_start(); // Inicia a sessão

// Inclui o arquivo de conexão com o banco de dados
include('partials/db.php');

// Verifica se o usuário está logado e se é um professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php'); // Redireciona para a página de login se não for professor
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$professor_nome = $_SESSION['usuario_nome'] ?? 'Professor Designado'; // Nome do professor logado
$feedback_message = '';
$feedback_type = '';

// Array para armazenar os pareceres designados a este professor
$pareceres_designados = [];

try {
    // Consulta para buscar todos os pareceres designados a este professor
    // A query reflete que `id_disciplina` NÃO está mais diretamente na tabela `pareceres`
    $stmt_designados = $pdo->prepare("
        SELECT 
            p.id, p.id_aluno, p.id_turma, p.unidade, p.periodo, p.status, p.data_criacao,
            a.nome AS nome_aluno,
            t.nome AS nome_turma,
            t.ano_letivo
            
        FROM 
            pareceres p
        JOIN 
            alunos a ON p.id_aluno = a.id
        JOIN 
            turmas t ON p.id_turma = t.id
        WHERE 
            p.id_professor_designado = :professor_id
        ORDER BY 
            t.nome, p.unidade, a.nome
    ");
    $stmt_designados->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
    $stmt_designados->execute();
    $pareceres_designados = $stmt_designados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar pareceres designados ao professor: " . $e->getMessage());
    $feedback_message = 'Erro ao carregar sua lista de pareceres. Por favor, tente novamente mais tarde.';
    $feedback_type = 'error';
}

// Verifica se há alguma mensagem de feedback na sessão (ex: de salvar_parecer_professor.php)
if (isset($_SESSION['feedback_message'])) {
    $feedback_message = $_SESSION['feedback_message']['text'];
    $feedback_type = $_SESSION['feedback_message']['type'];
    unset($_SESSION['feedback_message']); // Limpa a mensagem após exibir
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Meus Pareceres Designados</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>"
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2/select2.min.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css"); ?>"
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>"
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png" />
    <style>
        /* Estilos para as mensagens de feedback */
        .feedback-message-container {
            margin-bottom: 25px;
            text-align: center;
        }
        .feedback-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px; /* Espaço entre múltiplas mensagens */
            font-weight: bold;
            opacity: 0; /* Começa invisível */
            transform: translateY(-20px); /* Começa um pouco acima */
            animation: fadeInSlideDown 0.5s ease-out forwards; /* Animação */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .feedback-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .feedback-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6fb;
        }
        .feedback-message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Animação para as mensagens de feedback */
        @keyframes fadeInSlideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Estilos específicos para a tabela de pareceres */
        .table th, .table td {
            vertical-align: middle;
            font-size: 0.9em;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.8em;
            text-transform: capitalize;
        }
        /* Definição das cores para os novos status */
        .status-pendente_professor { background-color: #ffc107; color: #343a40; } /* Amarelo */
        .status-finalizado_professor { background-color: #17a2b8; color: white; } /* Azul ciano */
        .status-pendente_coordenador { background-color: #6f42c1; color: white; } /* Roxo */
        .status-finalizado_coordenador { background-color: #28a745; color: white; } /* Verde */
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
      <?php include('partials/_navbar.php'); ?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include('partials/_sidebar.php'); ?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Meus Pareceres Designados </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Meus Pareceres</li>
                </ol>
              </nav>
            </div>
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Lista de Pareceres Pendentes e Concluídos</h4>
                    <p class="card-description">Aqui você pode ver os pareceres que foram designados a você para preencher ou revisar.</p>

                    <?php if ($feedback_message): ?>
                        <div class="feedback-message-container">
                            <div class="feedback-message <?php echo $feedback_type; ?>">
                                <?php echo htmlspecialchars($feedback_message); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Aluno</th>
                            <th>Turma</th>
                            <th>Unidade</th>
                            <th>Período</th>
                            <th>Status do Parecer</th>
                            <th>Ações</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($pareceres_designados)): ?>
                            <tr>
                              <td colspan="6" class="text-center">Nenhum parecer designado encontrado para você.</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($pareceres_designados as $parecer): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($parecer['nome_aluno']); ?></td>
                                <td><?php echo htmlspecialchars($parecer['nome_turma'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($parecer['ano_letivo'] ?? 'N/A'); ?>)</td>
                                <td><?php echo htmlspecialchars($parecer['unidade']); ?>°</td>
                                <td><?php echo htmlspecialchars($parecer['periodo']); ?></td>
                                <td>
                                    <?php 
                                        $status_display = str_replace('_', ' ', $parecer['status']);
                                        $status_class = 'status-' . $parecer['status'];
                                        echo "<span class='status-badge {$status_class}'>" . htmlspecialchars(ucfirst($status_display)) . "</span>";
                                    ?>
                                </td>
                                <td>
                                  <?php 
                                    // O botão de ação leva para avaliar_aluno.php passando o ID do parecer
                                  ?>
                                  <a href="avaliar_aluno.php?id_parecer=<?php echo htmlspecialchars($parecer['id']); ?>" 
                                     class="btn btn-gradient-info btn-sm">
                                    <?php 
                                        if ($parecer['status'] == 'pendente_professor') {
                                            echo 'Preencher Parecer';
                                        } elseif ($parecer['status'] == 'finalizado_professor' || $parecer['status'] == 'pendente_coordenador') {
                                            echo 'Revisar Parecer';
                                        } else { // finalizado_coordenador
                                            echo 'Ver Parecer Finalizado';
                                        }
                                    ?>
                                  </a>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <?php include('partials/_footer.php'); ?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="<?php echo getAssetUrl("assets/vendors/select2/select2.min.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/vendors/typeahead.js/typeahead.bundle.min.js"); ?>"</script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"</script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="<?php echo getAssetUrl("assets/js/file-upload.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/typeahead.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/select2.js"); ?>"</script>
    <!-- End custom js for this page -->
  </body>
</html>
