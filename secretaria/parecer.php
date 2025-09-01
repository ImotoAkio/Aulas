<?php
/**
 * secretaria/parecer.php
 *
 * Esta página permite que um usuário com perfil de 'coordenador' (secretaria)
 * crie novos pareceres pedagógicos para ALUNOS DE UMA TURMA ESPECÍFICA,
 * designando uma turma, unidade e professor responsável.
 * (A disciplina NÃO é mais um campo a ser selecionado na criação do parecer,
 * pois o parecer é uma avaliação geral do professor designado para o aluno/turma/unidade.)
 *
 * Também lista os pareceres que já foram finalizados.
 *
 * Utiliza a estrutura de template existente com partials.
 */

session_start(); // Inicia a sessão

// Inclui o arquivo de conexão com o banco de dados
include('partials/db.php');

// Verifica se o usuário está logado e se é um coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: ../login.php'); // Redireciona para a página de login se não for coordenador
    exit();
}

$feedback_message = '';
$feedback_type = '';

// Processa o formulário quando enviado via POST para criar pareceres em lote por turma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_turma = filter_input(INPUT_POST, 'id_turma', FILTER_VALIDATE_INT);
    $unidade = filter_input(INPUT_POST, 'unidade', FILTER_SANITIZE_STRING); // ENUM '1', '2', '3', '4'
    $id_professor_designado = filter_input(INPUT_POST, 'id_professor_designado', FILTER_VALIDATE_INT);
    // REMOVIDO: $id_disciplina = filter_input(INPUT_POST, 'id_disciplina', FILTER_VALIDATE_INT); // Não é mais um campo
    $periodo = filter_input(INPUT_POST, 'periodo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validação inicial dos dados (id_disciplina removido da validação)
    if (!$id_turma || empty($unidade) || !$id_professor_designado || empty($periodo)) {
        $feedback_message = 'Erro: Por favor, preencha todos os campos para criar os pareceres.';
        $feedback_type = 'error';
    } else {
        try {
            // 1. Busca todos os alunos da turma selecionada
            $stmt_alunos_turma = $pdo->prepare("SELECT id, nome FROM alunos WHERE turma_id = :id_turma ORDER BY nome");
            $stmt_alunos_turma->bindParam(':id_turma', $id_turma, PDO::PARAM_INT);
            $stmt_alunos_turma->execute();
            $alunos_na_turma = $stmt_alunos_turma->fetchAll(PDO::FETCH_ASSOC);

            if (empty($alunos_na_turma)) {
                $feedback_message = 'Não há alunos nesta turma para criar pareceres.';
                $feedback_type = 'info'; // Alterado para info se não houver alunos
            } else {
                $created_count = 0; // Contador de pareceres criados
                $skipped_count = 0; // Contador de pareceres ignorados (já existentes)
                $error_count = 0;   // Contador de erros

                // Inicia uma transação para garantir a atomicidade das operações em lote
                $pdo->beginTransaction();

                foreach ($alunos_na_turma as $aluno) {
                    $aluno_id = $aluno['id'];
                    $aluno_nome = htmlspecialchars($aluno['nome']);

                    // 2. Verifica se já existe um parecer com esta combinação única
                    // (id_disciplina removido do CHECK na UNIQUE KEY)
                    $stmt_check = $pdo->prepare("
                        SELECT id FROM pareceres
                        WHERE id_aluno = :id_aluno 
                        AND id_turma = :id_turma 
                        AND unidade = :unidade 
                        AND id_professor_designado = :id_professor_designado 
                        AND periodo = :periodo
                    ");
                    $stmt_check->bindParam(':id_aluno', $aluno_id, PDO::PARAM_INT);
                    $stmt_check->bindParam(':id_turma', $id_turma, PDO::PARAM_INT);
                    $stmt_check->bindParam(':unidade', $unidade, PDO::PARAM_STR);
                    $stmt_check->bindParam(':id_professor_designado', $id_professor_designado, PDO::PARAM_INT);
                    $stmt_check->bindParam(':periodo', $periodo, PDO::PARAM_STR);
                    $stmt_check->execute();

                    if ($stmt_check->fetch()) {
                        $skipped_count++;
                    } else {
                        // 3. Insere o novo parecer na tabela `pareceres`
                        // (id_disciplina removido da inserção)
                        $stmt_insert = $pdo->prepare(
                            "INSERT INTO pareceres (id_aluno, id_turma, unidade, id_professor_designado, periodo, status)
                             VALUES (:id_aluno, :id_turma, :unidade, :id_professor_designado, :periodo, 'pendente_professor')"
                        );
                        $stmt_insert->bindParam(':id_aluno', $aluno_id, PDO::PARAM_INT);
                        $stmt_insert->bindParam(':id_turma', $id_turma, PDO::PARAM_INT);
                        $stmt_insert->bindParam(':unidade', $unidade, PDO::PARAM_STR);
                        $stmt_insert->bindParam(':id_professor_designado', $id_professor_designado, PDO::PARAM_INT);
                        $stmt_insert->bindParam(':periodo', $periodo, PDO::PARAM_STR);

                        if ($stmt_insert->execute()) {
                            $created_count++;
                        } else {
                            $error_count++;
                            error_log("Erro ao criar parecer para aluno {$aluno_id} na turma {$id_turma}: " . $stmt_insert->errorInfo()[2]);
                        }
                    }
                }

                // Finaliza a transação
                $pdo->commit();

                // Constrói a mensagem de feedback final
                if ($created_count > 0 || $skipped_count > 0) {
                    $feedback_message = "Criação de pareceres para a turma concluída:<br>";
                    if ($created_count > 0) {
                        $feedback_message .= "- {$created_count} parecer(es) criado(s).<br>";
                        $feedback_type = 'success';
                    }
                    if ($skipped_count > 0) {
                        $feedback_message .= "- {$skipped_count} parecer(es) já existente(s) foram ignorado(s).<br>";
                        if ($feedback_type == '') $feedback_type = 'info'; // Se nada foi criado, mas algo ignorado
                    }
                    if ($error_count > 0) {
                        $feedback_message .= "- {$error_count} parecer(es) com erro na criação. Verifique os logs.<br>";
                        $feedback_type = 'error'; // Prioriza erro se houver
                    }
                } else {
                    $feedback_message = 'Nenhum parecer foi criado ou ignorado para esta turma.';
                    $feedback_type = 'info';
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack(); // Desfaz a transação em caso de qualquer exceção
            error_log("Erro no banco de dados ao criar pareceres em lote: " . $e->getMessage());
            $feedback_message = 'Erro no banco de dados ao criar pareceres. Por favor, tente novamente.'; // Mensagem genérica para o usuário
            $feedback_type = 'error';
        }
    }
}

// Carregar dados para os dropdowns
$turmas = [];
$professores = [];
// REMOVIDO: $disciplinas = []; // Disciplinas não são mais necessárias para a criação do parecer aqui
$unidades_enum = ['1', '2', '3', '4']; // Hardcoded options for UNIDADE ENUM

try {
    $turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $professores = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'professor' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    // REMOVIDO: $disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao carregar dados para dropdowns: " . $e->getMessage());
    $feedback_message .= ($feedback_message ? "<br>" : "") . 'Erro ao carregar opções para o formulário. Por favor, tente novamente.'; // Mensagem genérica
    $feedback_type = 'error';
}

// Carregar pareceres finalizados para exibição na tabela (Coordenador/Secretaria vê TODOS)
$pareceres_finalizados = [];
try {
    // A query agora reflete a ausência de `id_disciplina` na tabela `pareceres`
    // e o `GROUP_CONCAT` para disciplinas foi removido.
    $stmt_finalizados = $pdo->prepare("
        SELECT 
            p.id_aluno, p.id_turma, p.unidade, p.periodo,
            MAX(p.status) AS status_geral, 
            MAX(p.data_criacao) AS ultima_data_criacao,
            a.nome AS nome_aluno,
            t.nome AS nome_turma,
            t.ano_letivo,
            (SELECT GROUP_CONCAT(DISTINCT u2.nome SEPARATOR '; ') 
             FROM pareceres p2 
             JOIN usuarios u2 ON p2.id_professor_designado = u2.id 
             WHERE p2.id_aluno = p.id_aluno 
               AND p2.id_turma = p.id_turma 
               AND p2.unidade = p.unidade 
               AND p2.periodo = p.periodo 
             GROUP BY p2.id_aluno, p2.id_turma, p2.unidade, p2.periodo) AS professores_designados_nesta_combinacao
        FROM 
            pareceres p
        JOIN 
            alunos a ON p.id_aluno = a.id
        JOIN 
            turmas t ON p.id_turma = t.id
        GROUP BY
            p.id_aluno, p.id_turma, p.unidade, p.periodo, a.nome, t.nome, t.ano_letivo
        HAVING 
            MAX(p.status) IN ('finalizado_coordenador', 'finalizado_professor')
        ORDER BY 
            t.nome, p.periodo, a.nome
    ");
    $stmt_finalizados->execute();
    $pareceres_finalizados = $stmt_finalizados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar pareceres finalizados: " . $e->getMessage());
    $feedback_message .= ($feedback_message ? "<br>" : "") . 'Erro ao carregar a lista de pareceres finalizados. Por favor, tente novamente.'; // Mensagem genérica
    $feedback_type = 'error';
}

// Mensagens de feedback da sessão (se vierem de outra página)
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
    <title>Gerenciar Pareceres</title>
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
        .status-pendente_professor { background-color: #ffc107; color: #343a40; } /* Amarelo */
        .status-finalizado_professor { background-color: #17a2b8; color: white; } /* Azul ciano */
        .status-pendente_coordenador { background-color: #6f42c1; color: white; } /* Roxo */
        .status-finalizado_coordenador { background-color: #28a745; color: white; } /* Verde */
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:../partials/_navbar.html -->
      <?php include('partials/_navbar.php'); ?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include('partials/_sidebar.php'); ?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Gerenciar Pareceres Pedagógicos </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Gerenciar Pareceres</li>
                </ol>
              </nav>
            </div>
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Criar Novo Parecer e Designar</h4>

                    <?php if ($feedback_message): ?>
                        <div class="feedback-message-container">
                            <div class="feedback-message <?php echo $feedback_type; ?>">
                                <?php echo htmlspecialchars($feedback_message); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="parecer.php" method="POST" class="forms-sample">
                      <div class="form-group">
                        <label for="id_turma">Turma</label>
                        <select class="form-control" id="id_turma" name="id_turma" required>
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo htmlspecialchars($turma['id']); ?>">
                                    <?php echo htmlspecialchars($turma['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label for="unidade">Unidade</label>
                        <select class="form-control" id="unidade" name="unidade" required>
                            <option value="">Selecione a unidade</option>
                            <?php foreach ($unidades_enum as $unidade_val): ?>
                                <option value="<?php echo htmlspecialchars($unidade_val); ?>">
                                    <?php echo htmlspecialchars($unidade_val) . "° Unidade"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label for="id_professor_designado">Professor Responsável</label>
                        <select class="form-control" id="id_professor_designado" name="id_professor_designado" required>
                            <option value="">Selecione um professor</option>
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?php echo htmlspecialchars($professor['id']); ?>">
                                    <?php echo htmlspecialchars($professor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label for="periodo">Período Letivo</label>
                        <input type="text" class="form-control" id="periodo" name="periodo" placeholder="Ex: 2025/1 ou Anual 2024" required>
                      </div>
                      
                      <button type="submit" class="btn btn-gradient-primary me-2">Criar e Designar Parecer(es) por Turma</button>
                      <button type="reset" class="btn btn-light">Limpar Formulário</button>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Pareceres Finalizados</h4>
                    <p class="card-description">Lista de pareceres que já foram concluídos por professores ou coordenadores.</p>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Aluno</th>
                            <th>Turma</th>
                            <th>Unidade</th>
                            <th>Período</th>
                            <th>Professor Designado</th>
                            <th>Status</th>
                            <th>Ações</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($pareceres_finalizados)): ?>
                            <tr>
                              <td colspan="7" class="text-center">Nenhum parecer finalizado encontrado.</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($pareceres_finalizados as $parecer): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($parecer['nome_aluno']); ?></td>
                                <td><?php echo htmlspecialchars($parecer['nome_turma'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($parecer['ano_letivo'] ?? 'N/A'); ?>)</td>
                                <td><?php echo htmlspecialchars($parecer['unidade']); ?>°</td>
                                <td><?php echo htmlspecialchars($parecer['periodo']); ?></td>
                                <td><?php echo htmlspecialchars($parecer['professores_designados_nesta_combinacao']); ?></td>
                                <td>
                                    <?php 
                                        $status_display = str_replace('_', ' ', $parecer['status_geral']);
                                        $status_class = 'status-' . $parecer['status_geral'];
                                        echo "<span class='status-badge {$status_class}'>" . htmlspecialchars(ucfirst($status_display)) . "</span>";
                                    ?>
                                </td>
                                <td>
                                  <a href="ver_parecer.php?id_aluno=<?php echo htmlspecialchars($parecer['id_aluno']); ?>&periodo=<?php echo urlencode($parecer['periodo']); ?>" 
                                     class="btn btn-info btn-sm" target="_blank">Ver Detalhes</a>
                                     
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
          <!-- partial:../partials/_footer.html -->
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
