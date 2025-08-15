<?php
/**
 * professor/ver_notas.php
 *
 * Esta página permite que um professor visualize um resumo das notas
 * de seus alunos, e também insira notas sequencialmente por turma e disciplina.
 * Tudo é gerenciado dentro da mesma página.
 *
 * Utiliza a estrutura de template existente com partials.
 */

// Ativar exibição de erros (para depuração, remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Inclui o arquivo de conexão com o banco de dados
include('partials/db.php');

// Verifica se o usuário está logado e se é um professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    header('Location: ../login.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Inicializa variáveis para evitar notices e para controle do fluxo
$turma_id = $_GET['turma_id'] ?? null;
$disciplina_id = $_GET['disciplina_id'] ?? null;
$unidade_get = $_GET['unidade'] ?? null;
$unidade = $unidade_get !== null ? (int) $unidade_get : null;
$aluno_id_get = $_GET['aluno_id'] ?? null; // ID do aluno atual para inserção sequencial
$exibir_tabela_resumo = isset($_GET['finalizado']) && $_GET['finalizado'] === '1'; // Flag para exibir resumo final

$turmas_do_professor = [];
$disciplinas_do_professor = [];
$result_alunos_da_turma = []; // Lista de todos os alunos da turma selecionada (para navegação sequencial)
$alunos_com_notas_para_resumo = []; // Dados para a tabela de resumo de notas (todas as unidades)
$current_aluno_para_form = null; // Aluno atual para o formulário sequencial


// --- Processar envio de notas (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aluno_id_post = $_POST['aluno_id'];
    $turma_id_post = $_POST['turma_id'];
    $disciplina_id_post = $_POST['disciplina_id'];
    $unidade_post = (int) $_POST['unidade'];
    $nota_1_post = $_POST['nota_1'];
    $nota_2_post = $_POST['nota_2'];
    
    $media = ($nota_1_post + $nota_2_post) / 2;
    $media_column = "media_" . $unidade_post;

    try {
        // Inicia uma transação para garantir a atomicidade
        $pdo->beginTransaction();

        // Verifica se a nota já existe para esta combinação (aluno, turma, disciplina, unidade)
        $stmt_check = $pdo->prepare("SELECT id FROM notas WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND disciplina_id = :disciplina_id AND unidade = :unidade");
        $stmt_check->execute([
            ':aluno_id' => $aluno_id_post,
            ':turma_id' => $turma_id_post,
            ':disciplina_id' => $disciplina_id_post,
            ':unidade' => $unidade_post
        ]);

        if ($stmt_check->rowCount() === 0) {
            // Insere nova nota
            $stmt_insert = $pdo->prepare("INSERT INTO notas (aluno_id, turma_id, disciplina_id, unidade, $media_column) VALUES (:aluno_id, :turma_id, :disciplina_id, :unidade, :media)");
            $stmt_insert->execute([
                ':aluno_id' => $aluno_id_post,
                ':turma_id' => $turma_id_post,
                ':disciplina_id' => $disciplina_id_post,
                ':unidade' => $unidade_post,
                ':media' => $media
            ]);
        } else {
            // Atualiza nota existente
            $stmt_update = $pdo->prepare("UPDATE notas SET $media_column = :media WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND disciplina_id = :disciplina_id AND unidade = :unidade");
            $stmt_update->execute([
                ':media' => $media,
                ':aluno_id' => $aluno_id_post,
                ':turma_id' => $turma_id_post,
                ':disciplina_id' => $disciplina_id_post,
                ':unidade' => $unidade_post // Adicionado unidade na cláusula WHERE para UPDATE preciso
            ]);
        }

        $pdo->commit(); // Confirma a transação

        // Lógica para o próximo aluno (redirecionamento sequencial)
        $stmt_alunos_turma_seq = $pdo->prepare("SELECT id FROM alunos WHERE turma_id = :turma_id ORDER BY nome");
        $stmt_alunos_turma_seq->bindParam(':turma_id', $turma_id_post);
        $stmt_alunos_turma_seq->execute();
        $alunos_ids_sequencial = $stmt_alunos_turma_seq->fetchAll(PDO::FETCH_COLUMN); // Apenas IDs

        $current_index = array_search($aluno_id_post, array_column($result_alunos_da_turma, 'id'));
        $next_index = $current_index + 1;

        if (isset($result_alunos_da_turma[$next_index])) { // Verifica se há um próximo aluno na lista completa
            $next_aluno_id = $result_alunos_da_turma[$next_index]['id'];
            header("Location: ver_notas.php?turma_id=$turma_id_post&disciplina_id=$disciplina_id_post&unidade=$unidade_post&aluno_id=$next_aluno_id");
        } else {
            // Todos os alunos foram processados
            header("Location: ver_notas.php?turma_id=$turma_id_post&disciplina_id=$disciplina_id_post&unidade=$unidade_post&finalizado=1");
        }
        exit(); // Termina o script após o redirecionamento

    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz a transação em caso de erro
        error_log("Erro ao salvar nota: " . $e->getMessage());
        // Redirecionar de volta para o aluno atual com mensagem de erro
        header("Location: ver_notas.php?turma_id=$turma_id_post&disciplina_id=$disciplina_id_post&unidade=$unidade_post&aluno_id=$aluno_id_post&erro=1");
        exit();
    }
}


// --- Fetch Turmas e Disciplinas (para o formulário de filtro e seleção) ---
try {
    $stmt_turmas = $pdo->prepare("
        SELECT t.id, t.nome FROM turmas t
        JOIN professores_turmas pt ON t.id = pt.turma_id
        WHERE pt.professor_id = :professor_id
        ORDER BY t.nome
    ");
    $stmt_turmas->bindParam(':professor_id', $professor_id);
    $stmt_turmas->execute();
    $turmas_do_professor = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas do professor: " . $e->getMessage());
    // Fallback ou feedback apropriado
}

try {
    $stmt_disciplinas = $pdo->prepare("
        SELECT d.id, d.nome FROM disciplinas d
        JOIN professores_disciplinas pd ON d.id = pd.disciplina_id
        WHERE pd.professor_id = :professor_id
        ORDER BY d.nome
    ");
    $stmt_disciplinas->bindParam(':professor_id', $professor_id);
    $stmt_disciplinas->execute();
    $disciplinas_do_professor = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar disciplinas do professor: " . $e->getMessage());
    // Fallback ou feedback apropriado
}


// --- Lógica para buscar dados de alunos para o formulário de inserção OU para a tabela de resumo ---

// Só tenta buscar dados e exibir se os filtros básicos (turma e disciplina) estão selecionados
if ($turma_id && $disciplina_id) {
    // Buscar TODOS os alunos da turma para a navegação sequencial e para a tabela de resumo
    $stmt_alunos_da_turma_completa = $pdo->prepare("SELECT id, nome FROM alunos WHERE turma_id = :turma_id ORDER BY nome");
    $stmt_alunos_da_turma_completa->bindParam(':turma_id', $turma_id, PDO::PARAM_INT);
    $stmt_alunos_da_turma_completa->execute();
    $result_alunos_da_turma = $stmt_alunos_da_turma_completa->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result_alunos_da_turma)) {
        // Se a turma não tem alunos, resetar filtros para exibir a mensagem inicial no HTML
        $turma_id = null; 
        $disciplina_id = null;
        $unidade = null; // Garante que a seção de conteúdo não seja exibida sem alunos
        $aluno_id_get = null; // Também zera o aluno_id_get
    } else {
        // Buscar todas as notas para a tabela de resumo (para a turma e disciplina selecionadas, em todas as unidades)
        // Isso é feito independentemente da 'unidade' estar selecionada para a tabela resumo sempre aparecer.
        $stmt_alunos_notas_resumo = $pdo->prepare("
            SELECT 
                a.id AS aluno_id, 
                a.nome AS aluno_nome, 
                MAX(CASE WHEN n.unidade = 1 THEN n.media_1 END) AS media_1,
                MAX(CASE WHEN n.unidade = 2 THEN n.media_2 END) AS media_2,
                MAX(CASE WHEN n.unidade = 3 THEN n.media_3 END) AS media_3,
                MAX(CASE WHEN n.unidade = 4 THEN n.media_4 END) AS media_4
            FROM 
                alunos a
            LEFT JOIN 
                notas n ON a.id = n.aluno_id AND n.turma_id = :turma_id_resumo AND n.disciplina_id = :disciplina_id_resumo
            WHERE 
                a.turma_id = :turma_id_resumo
            GROUP BY a.id, a.nome
            ORDER BY a.nome
        ");
        $stmt_alunos_notas_resumo->bindParam(':turma_id_resumo', $turma_id, PDO::PARAM_INT);
        $stmt_alunos_notas_resumo->bindParam(':disciplina_id_resumo', $disciplina_id, PDO::PARAM_INT);
        $stmt_alunos_notas_resumo->execute();
        $alunos_com_notas_para_resumo = $stmt_alunos_notas_resumo->fetchAll(PDO::FETCH_ASSOC);

        // Determinar o aluno atual para o formulário sequencial, SOMENTE se uma unidade também estiver selecionada
        if ($unidade) { 
            if ($aluno_id_get) {
                $aluno_ids_array = array_column($result_alunos_da_turma, 'id');
                $current_index = array_search($aluno_id_get, $aluno_ids_array);
                if ($current_index === false) { // aluno_id_get inválido, volta para o primeiro da lista
                    $current_aluno_para_form = $result_alunos_da_turma[0];
                } else {
                    $current_aluno_para_form = $result_alunos_da_turma[$current_index];
                }
            } else {
                // Se nenhum aluno_id_get mas unidade está selecionada, começa pelo primeiro aluno para inserção sequencial
                $current_aluno_para_form = $result_alunos_da_turma[0];
            }

            // Buscar notas existentes para o aluno atual para pré-preencher o formulário
            $stmt_nota_existente = $pdo->prepare("SELECT media_1, media_2 FROM notas WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND disciplina_id = :disciplina_id AND unidade = :unidade");
            $stmt_nota_existente->execute([
                ':aluno_id' => $current_aluno_para_form['id'],
                ':turma_id' => $turma_id,
                ':disciplina_id' => $disciplina_id,
                ':unidade' => $unidade
            ]);
            $nota_existente = $stmt_nota_existente->fetch(PDO::FETCH_ASSOC);
            // Anexa as notas existentes ao objeto do aluno para fácil acesso no HTML
            $current_aluno_para_form['nota_1_existente'] = $nota_existente['media_1'] ?? '';
            $current_aluno_para_form['nota_2_existente'] = $nota_existente['media_2'] ?? '';
        }
    }
}

// Unidades para o dropdown
$unidades_options = [1, 2, 3, 4];

// Seleciona o nome da turma e disciplina para exibição no título da tabela/formulário
$selected_turma_name = array_values(array_filter($turmas_do_professor, fn($t) => $t['id'] == $turma_id))[0]['nome'] ?? 'N/A';
$selected_disciplina_name = array_values(array_filter($disciplinas_do_professor, fn($d) => $d['id'] == $disciplina_id))[0]['nome'] ?? 'N/A';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notas do Professor</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
    <!-- inject:css -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Layout styles -->
    <link rel="shortcut icon" href="../assets/images/favicon.png" />
    <style>
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include 'partials/_navbar.php'; ?>
        <div class="container-fluid page-body-wrapper">
            <?php include 'partials/_sidebar.php'; ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Visualizar Notas</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Visualizar Notas</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Selecionar Turma, Disciplina e Unidade</h4>
                                    <p class="card-description">
                                        Use os filtros para gerenciar as notas.
                                    </p>

                                    <form method="GET" action="ver_notas.php" class="forms-sample">
                                        <div class="form-group">
                                            <label for="turma_id">Turma</label>
                                            <select name="turma_id" id="turma_id" class="form-control" required>
                                                <option value="">Selecione a turma</option>
                                                <?php foreach ($turmas_do_professor as $turma): ?>
                                                    <option value="<?= htmlspecialchars($turma['id']) ?>" <?= ($turma_id == $turma['id'] ? 'selected' : '') ?>>
                                                        <?= htmlspecialchars($turma['nome']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="disciplina_id">Disciplina</label>
                                            <select name="disciplina_id" id="disciplina_id" class="form-control" required>
                                                <option value="">Selecione a disciplina</option>
                                                <?php foreach ($disciplinas_do_professor as $disciplina): ?>
                                                    <option value="<?= htmlspecialchars($disciplina['id']) ?>" <?= ($disciplina_id == $disciplina['id'] ? 'selected' : '') ?>>
                                                        <?= htmlspecialchars($disciplina['nome']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="unidade">Unidade</label>
                                            <select name="unidade" id="unidade" class="form-control" required>
                                                <option value="">Selecione a unidade</option>
                                                <?php foreach ($unidades_options as $u_opt): ?>
                                                    <option value="<?= $u_opt ?>" <?= ($unidade == $u_opt ? 'selected' : '') ?>>
                                                        <?= $u_opt ?>ª Unidade
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-gradient-primary me-2">Continuar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($turma_id && $disciplina_id): // Se Turma e Disciplina foram selecionadas, exibirá o conteúdo principal ?>

                        
                        <?php // Seção para a TABELA DE RESUMO DE NOTAS (Sempre visível se turma e disciplina selecionadas) ?>
                        <div class="row mt-4">
                            <div class="col-lg-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Resumo das Notas da Turma: 
                                            <?= htmlspecialchars($selected_turma_name) ?> 
                                            - Disciplina: <?= htmlspecialchars($selected_disciplina_name) ?>
                                        </h4>
                                        <p class="card-description">Notas inseridas para todas as unidades disponíveis.</p>
                                        <?php if ($exibir_tabela_resumo): ?>
                                            <div class="alert alert-info">Notas inseridas para todos os alunos desta combinação Turma/Disciplina/Unidade.</div>
                                        <?php endif; ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Aluno</th>
                                                        <th>1ª Unidade</th>
                                                        <th>2ª Unidade</th>
                                                        <th>3ª Unidade</th>
                                                        <th>4ª Unidade</th>

                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($alunos_com_notas_para_resumo)): ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center">Nenhuma nota encontrada para a turma e disciplina selecionadas.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($alunos_com_notas_para_resumo as $aluno_nota): ?>
                                                            <?php
                                                                $medias_unidades = [];
                                                                if (isset($aluno_nota['media_1']) && $aluno_nota['media_1'] !== null) $medias_unidades[] = $aluno_nota['media_1'];
                                                                if (isset($aluno_nota['media_2']) && $aluno_nota['media_2'] !== null) $medias_unidades[] = $aluno_nota['media_2'];
                                                                if (isset($aluno_nota['media_3']) && $aluno_nota['media_3'] !== null) $medias_unidades[] = $aluno_nota['media_3'];
                                                                if (isset($aluno_nota['media_4']) && $aluno_nota['media_4'] !== null) $medias_unidades[] = $aluno_nota['media_4'];
                                                                
                                                                $media_final_aluno = count($medias_unidades) > 0 ? array_sum($medias_unidades) / count($medias_unidades) : '-';
                                                            ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($aluno_nota['aluno_nome']) ?></td>
                                                                <td><?= htmlspecialchars($aluno_nota['media_1'] ?? '-') ?></td>
                                                                <td><?= htmlspecialchars($aluno_nota['media_2'] ?? '-') ?></td>
                                                                <td><?= htmlspecialchars($aluno_nota['media_3'] ?? '-') ?></td>
                                                                <td><?= htmlspecialchars($aluno_nota['media_4'] ?? '-') ?></td>

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
                    <?php else: // Exibir o formulário de filtro inicial ou mensagem de prompt ?>
                        <div class="row">
                            <div class="col-12 text-center">
                                <p class="text-info">Por favor, selecione a turma, disciplina e unidade para gerenciar as notas.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <!-- content-wrapper ends -->
                <?php include 'partials/_footer.php'; ?>
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- inject:js -->
    <script src="../assets/js/off-canvas.js"></script>
    <script src="../assets/js/misc.js"></script>
    <script src="../assets/js/settings.js"></script>
    <script src="../assets/js/todolist.js"></script>
    <script src="../assets/js/jquery.cookie.js"></script>
    <!-- End custom js for this page -->
</body>
</html>