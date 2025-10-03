<?php
/**
 * professor/avaliar_aluno.php
 *
 * Esta página permite que um professor preencha ou edite um parecer pedagógico
 * para um aluno específico, que foi previamente designado a ele pela secretaria.
 * O parecer é uma avaliação GERAL do aluno pelo professor, sem campo de disciplina.
 *
 * Parâmetros GET esperados:
 * - id_parecer: ID do parecer a ser preenchido/editado.
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
$professor_nome = $_SESSION['usuario_nome'] ?? 'Professor Desconhecido'; // Nome do professor logado
$feedback_message = '';
$feedback_type = '';

// Recebe o ID do parecer da URL
$id_parecer_url = filter_input(INPUT_GET, 'id_parecer', FILTER_VALIDATE_INT);

// Validação do parâmetro essencial
if (!$id_parecer_url) {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Parecer não especificado.'];
    header('Location: parecer.php'); // Redireciona de volta para a lista de pareceres
    exit();
}

// Busca os dados do parecer a ser preenchido/editado
$parecer_data = null;
$aluno_info = null;
$turma_info = null;
$disciplina_contexto_info = null; // Informações de disciplina para contexto, não para salvar no parecer

try {
    // 1. Busca os dados do parecer principal e do aluno
    $stmt_parecer = $pdo->prepare("
        SELECT
            p.*,
            a.nome AS nome_aluno -- Obtém o nome do aluno diretamente
        FROM
            pareceres p
        JOIN
            alunos a ON p.id_aluno = a.id
        WHERE
            p.id = :id_parecer_url AND p.id_professor_designado = :professor_id
        LIMIT 1
    ");
    $stmt_parecer->bindParam(':id_parecer_url', $id_parecer_url, PDO::PARAM_INT);
    $stmt_parecer->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
    $stmt_parecer->execute();
    $parecer_data = $stmt_parecer->fetch(PDO::FETCH_ASSOC);

    if (!$parecer_data) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Parecer não encontrado ou você não tem permissão para acessá-lo.'];
        header('Location: parecer.php');
        exit();
    }

    // 2. Busca os dados da turma usando p.id_turma
    $stmt_turma = $pdo->prepare("SELECT nome AS nome_turma, ano_letivo FROM turmas WHERE id = :id_turma");
    $stmt_turma->bindParam(':id_turma', $parecer_data['id_turma'], PDO::PARAM_INT);
    $stmt_turma->execute();
    $turma_info = $stmt_turma->fetch(PDO::FETCH_ASSOC);

    // 3. Busca a disciplina principal do professor (para exibir como CONTEXTO, não para salvar no parecer)
    // Usamos professores_disciplinas para pegar UMA disciplina que o professor leciona
    $disciplina_contexto_nome = 'Não especificada';
    $stmt_disciplina_contexto = $pdo->prepare("
        SELECT d.nome FROM professores_disciplinas pd
        JOIN disciplinas d ON pd.disciplina_id = d.id
        WHERE pd.professor_id = :professor_id LIMIT 1
    ");
    $stmt_disciplina_contexto->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
    $stmt_disciplina_contexto->execute();
    $disciplina_contexto_info = $stmt_disciplina_contexto->fetch(PDO::FETCH_ASSOC);
    if ($disciplina_contexto_info) {
        $disciplina_contexto_nome = $disciplina_contexto_info['nome'];
    }


    // Merge os dados para facilitar a exibição
    $parecer_data['nome_turma'] = $turma_info['nome_turma'] ?? 'N/A';
    $parecer_data['ano_letivo'] = $turma_info['ano_letivo'] ?? 'N/A';
    $parecer_data['disciplina_contexto_professor'] = $disciplina_contexto_nome;


} catch (PDOException $e) {
    // Loga o erro detalhado para depuração
    error_log("Erro ao carregar parecer para avaliação (fase de busca): " . $e->getMessage());
    // Exibe uma mensagem genérica para o usuário
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao carregar os dados do parecer. Por favor, tente novamente mais tarde.'];
    header('Location: parecer.php');
    exit();
}

// Verifica se há alguma mensagem de feedback na sessão (ex: de salvar_parecer_professor.php)
if (isset($_SESSION['feedback_message'])) {
    $feedback_message = $_SESSION['feedback_message']['text'];
    $feedback_type = $_SESSION['feedback_message']['type'];
    unset($_SESSION['feedback_message']); // Limpa a mensagem após exibir
}

// Determina se o formulário deve ser somente leitura (se já finalizado pelo coordenador)
$is_read_only = ($parecer_data['status'] == 'finalizado_coordenador');

?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Avaliar Parecer: <?php echo htmlspecialchars($parecer_data['nome_aluno']); ?></title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="../assets/vendors/select2/select2.min.css">
    <link rel="stylesheet" href="../assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="shortcut icon" href="../assets/images/favicon.png" />
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
        /* Ajustes de estilo para os inputs e selects para corresponder ao template */
        .form-control {
            border-radius: 5px; /* Arredondamento padrão do template */
        }
        .form-select {
            border-radius: 5px; /* Arredondamento padrão do template */
        }
        .read-only-message {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
            text-align: center;
        }
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
              <h3 class="page-title"> Avaliar Parecer: <?php echo htmlspecialchars($parecer_data['nome_aluno']); ?> </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="parecer.php">Meus Pareceres</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Avaliar Parecer</li>
                </ol>
              </nav>
            </div>
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Preencher/Revisar Parecer</h4>

                    <?php if ($feedback_message): ?>
                        <div class="feedback-message-container">
                            <div class="feedback-message <?php echo $feedback_type; ?>">
                                <?php echo htmlspecialchars($feedback_message); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <p><strong>Aluno:</strong> <?php echo htmlspecialchars($parecer_data['nome_aluno']); ?></p>
                        <p><strong>Turma:</strong> <?php echo htmlspecialchars($parecer_data['nome_turma'] ?? 'N/A'); ?> (Ano: <?php echo htmlspecialchars($parecer_data['ano_letivo'] ?? 'N/A'); ?>)</p>
                        <p><strong>Unidade:</strong> <?php echo htmlspecialchars($parecer_data['unidade']); ?>°</p>
                        <p><strong>Período:</strong> <?php echo htmlspecialchars($parecer_data['periodo']); ?></p>
                        <p><strong>Professor Designado:</strong> <?php echo htmlspecialchars($professor_nome); ?></p>
                        <p><strong>Disciplina de Contexto:</strong> <?php echo htmlspecialchars($parecer_data['disciplina_contexto_professor']); ?> (Disciplina principal do professor)</p>
                        <p><strong>Status Atual:</strong> <span class="status-badge status-<?php echo htmlspecialchars($parecer_data['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $parecer_data['status']))); ?></span></p>
                    </div>

                    <?php if ($is_read_only): ?>
                        <div class="read-only-message">
                            Este parecer foi FINALIZADO pelo coordenador e não pode mais ser alterado.
                        </div>
                    <?php endif; ?>

                    <form action="salvar_parecer_professor.php" method="POST" class="forms-sample">
                        <!-- Campo oculto para passar o ID do parecer que está sendo avaliado -->
                        <input type="hidden" name="id_parecer" value="<?php echo htmlspecialchars($parecer_data['id']); ?>">
                        
                        <!-- PARTE A: Avaliação Geral (Panorama) -->
                        <h5 class="card-description">Avaliação Geral do Aluno</h5>
                        <p class="card-description text-info">Preencha com sua avaliação geral sobre o desempenho e comportamento do aluno neste período, não se limitando a uma única disciplina.</p>

                        <div class="form-group">
                            <label for="disposicao_aula">Disposição Geral na Aula:</label>
                            <select class="form-control" id="disposicao_aula" name="disposicao_aula" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="facilidade" <?php echo (isset($parecer_data['disposicao_aula']) && $parecer_data['disposicao_aula'] == 'facilidade') ? 'selected' : ''; ?>>Facilidade</option>
                                <option value="dificuldade" <?php echo (isset($parecer_data['disposicao_aula']) && $parecer_data['disposicao_aula'] == 'dificuldade') ? 'selected' : ''; ?>>Dificuldade</option>
                                <option value="interesse" <?php echo (isset($parecer_data['disposicao_aula']) && $parecer_data['disposicao_aula'] == 'interesse') ? 'selected' : ''; ?>>Interesse</option>
                                <option value="desinteresse" <?php echo (isset($parecer_data['disposicao_aula']) && $parecer_data['disposicao_aula'] == 'desinteresse') ? 'selected' : ''; ?>>Desinteresse</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="desempenho_geral">Desempenho Geral:</label>
                            <select class="form-control" id="desempenho_geral" name="desempenho_geral" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="acima" <?php echo (isset($parecer_data['desempenho_geral']) && $parecer_data['desempenho_geral'] == 'acima') ? 'selected' : ''; ?>>Acima do esperado</option>
                                <option value="dentro" <?php echo (isset($parecer_data['desempenho_geral']) && $parecer_data['desempenho_geral'] == 'dentro') ? 'selected' : ''; ?>>Dentro do esperado</option>
                                <option value="abaixo" <?php echo (isset($parecer_data['desempenho_geral']) && $parecer_data['desempenho_geral'] == 'abaixo') ? 'selected' : ''; ?>>Abaixo do esperado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="obs_geral_professor">Observações Gerais do Professor (Campo de texto):</label>
                            <textarea class="form-control" id="obs_geral_professor" name="obs_geral_professor" placeholder="Descreva suas observações gerais sobre o aluno..." <?php echo $is_read_only ? 'readonly' : ''; ?>><?php echo htmlspecialchars($parecer_data['obs_geral_professor'] ?? ''); ?></textarea>
                        </div>

                        <h5 class="card-description mt-5">Aspectos de Comportamento e Interação</h5>
                        <p class="card-description text-info">Avalie os aspectos de interação e postura do aluno.</p>

                        <div class="form-group">
                            <label for="comportamento">Comportamento em interações:</label>
                            <select class="form-control" id="comportamento" name="comportamento" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="colaborativo" <?php echo (isset($parecer_data['comportamento']) && $parecer_data['comportamento'] == 'colaborativo') ? 'selected' : ''; ?>>Colaborativo</option>
                                <option value="agressivo" <?php echo (isset($parecer_data['comportamento']) && $parecer_data['comportamento'] == 'agressivo') ? 'selected' : ''; ?>>Agressivo</option>
                                <option value="retraido" <?php echo (isset($parecer_data['comportamento']) && $parecer_data['comportamento'] == 'retraido') ? 'selected' : ''; ?>>Retraído</option>
                                <option value="proativo" <?php echo (isset($parecer_data['comportamento']) && $parecer_data['comportamento'] == 'proativo') ? 'selected' : ''; ?>>Proativo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="participacao_grupo">Participação em grupo:</label>
                            <select class="form-control" id="participacao_grupo" name="participacao_grupo" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="ativamente" <?php echo (isset($parecer_data['participacao_grupo']) && $parecer_data['participacao_grupo'] == 'ativamente') ? 'selected' : ''; ?>>Ativamente</option>
                                <option value="pouco" <?php echo (isset($parecer_data['participacao_grupo']) && $parecer_data['participacao_grupo'] == 'pouco') ? 'selected' : ''; ?>>Pouco</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="respeito_regras">Respeito às regras:</label>
                            <select class="form-control" id="respeito_regras" name="respeito_regras" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="sim" <?php echo (isset($parecer_data['respeito_regras']) && $parecer_data['respeito_regras'] == 'sim') ? 'selected' : ''; ?>>Sim</option>
                                <option value="nao" <?php echo (isset($parecer_data['respeito_regras']) && $parecer_data['respeito_regras'] == 'nao') ? 'selected' : ''; ?>>Não</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="postura_atividades">Postura geral nas atividades:</label>
                            <select class="form-control" id="postura_atividades" name="postura_atividades" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="seguranca" <?php echo (isset($parecer_data['postura_atividades']) && $parecer_data['postura_atividades'] == 'seguranca') ? 'selected' : ''; ?>>Segurança</option>
                                <option value="inseguranca" <?php echo (isset($parecer_data['postura_atividades']) && $parecer_data['postura_atividades'] == 'inseguranca') ? 'selected' : ''; ?>>Insegurança</option>
                                <option value="autonomia" <?php echo (isset($parecer_data['postura_atividades']) && $parecer_data['postura_atividades'] == 'autonomia') ? 'selected' : ''; ?>>Autonomia</option>
                                <option value="dependencia" <?php echo (isset($parecer_data['postura_atividades']) && $parecer_data['postura_atividades'] == 'dependencia') ? 'selected' : ''; ?>>Dependência</option>
                                <option value="neutra" <?php echo (isset($parecer_data['postura_atividades']) && $parecer_data['postura_atividades'] == 'neutra') ? 'selected' : ''; ?>>Neutra</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="postura_desafios">Postura diante de desafios:</label>
                            <select class="form-control" id="postura_desafios" name="postura_desafios" required <?php echo $is_read_only ? 'disabled' : ''; ?>>
                                <option value="">Selecione...</option>
                                <option value="resiliencia" <?php echo (isset($parecer_data['postura_desafios']) && $parecer_data['postura_desafios'] == 'resiliencia') ? 'selected' : ''; ?>>Resiliência</option>
                                <option value="frustracao" <?php echo (isset($parecer_data['postura_desafios']) && $parecer_data['postura_desafios'] == 'frustracao') ? 'selected' : ''; ?>>Frustração</option>
                                <option value="flexibilidade" <?php echo (isset($parecer_data['postura_desafios']) && $parecer_data['postura_desafios'] == 'flexibilidade') ? 'selected' : ''; ?>>Flexibilidade</option>
                                <option value="aceitacao" <?php echo (isset($parecer_data['postura_desafios']) && $parecer_data['postura_desafios'] == 'aceitacao') ? 'selected' : ''; ?>>Aceitação</option>
                            </select>
                        </div>
                        
                        <?php if (!$is_read_only): ?>
                            <button type="submit" class="btn btn-gradient-primary me-2">Salvar Parecer</button>
                            <button type="button" onclick="window.location.href='parecer.php'" class="btn btn-light">Voltar</button>
                        <?php else: ?>
                            <button type="button" onclick="window.location.href='parecer.php'" class="btn btn-secondary">Voltar</button>
                        <?php endif; ?>
                    </form>

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
    <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="../assets/vendors/select2/select2.min.js"></script>
    <script src="../assets/vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="../assets/js/off-canvas.js"></script>
    <script src="../assets/js/misc.js"></script>
    <script src="../assets/js/settings.js"></script>
    <script src="../assets/js/todolist.js"></script>
    <script src="../assets/js/jquery.cookie.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="../assets/js/file-upload.js"></script>
    <script src="../assets/js/typeahead.js"></script>
    <script src="../assets/js/select2.js"></script>
    <!-- End custom js for this page -->
  </body>
</html>
