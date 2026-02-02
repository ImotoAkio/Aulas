<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    header("Location: ../../login.php");
    exit;
}

$pdo = getConnection();

// Processar requisição AJAX para obter JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'obter_json') {
    $aluno_id = (int) ($_POST['aluno_id'] ?? 0);
    $todos = (isset($_POST['todos']) && $_POST['todos'] === '1');

    try {
        $query = "
            SELECT a.*, t.nome as turma_nome, t.ano_letivo
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            INNER JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
            WHERE a.status_cadastro IN ('pre_cadastro', 'completo', 'aprovado')
        ";

        $params = [];
        if (!$todos && $aluno_id > 0) {
            $query .= " AND a.id = ?";
            $params[] = $aluno_id;
        }

        $query .= " ORDER BY a.nome";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $json_output = [];

        foreach ($alunos as $aluno) {
            $json_item = [
                'class_room' => [
                    'name' => $aluno['turma_nome'] ?? '',
                    'grade_name' => $aluno['turma_nome'] ?? '', // Usando nome da turma como série, já que não temos campo específico
                    'academic_year' => $aluno['ano_letivo'] ?? date('Y'),
                    'shift' => 'matutino' // Placeholder fixo pois não há campo no banco
                ],
                'student' => [
                    'name' => $aluno['nome'],
                    'birth_date' => $aluno['data_nascimento'] ?? '',
                    'cpf' => $aluno['cpf'] ?? '',
                    'nis' => $aluno['nis'] ?? ''
                ],
                'guardian' => [
                    'name' => $aluno['nome_resp_legal'] ?? '',
                    'cpf' => $aluno['cpf_resp_legal'] ?? '',
                    'relationship' => $aluno['grau_parentesco_resp_legal'] ?? '',
                    'email' => $aluno['email'] ?? '',
                    'phone' => $aluno['telefone1'] ?? $aluno['telefone2'] ?? ''
                ],
                'address' => [
                    'street' => $aluno['endereco'] ?? '',
                    'number' => $aluno['numero'] ?? '',
                    'neighborhood' => $aluno['bairro'] ?? '',
                    'city' => $aluno['cidade'] ?? '',
                    'state' => $aluno['estado'] ?? '',
                    'cep' => $aluno['cep'] ?? '',
                    'zone' => 'urbana' // Placeholder fixo
                ]
            ];
            $json_output[] = $json_item;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => true, 'dados' => $json_output], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    } catch (PDOException $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'erro' => "Erro ao gerar JSON: " . $e->getMessage()]);
        exit;
    }
}

// Buscar pré-cadastros para listagem
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.cpf, a.status_cadastro, t.nome as turma_nome
        FROM alunos a
        INNER JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.status_cadastro IN ('pre_cadastro', 'completo', 'aprovado')
        ORDER BY pc.criado_em DESC
    ");
    $stmt->execute();
    $pre_cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pre_cadastros = [];
    $erro = "Erro ao buscar alunos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Dados JSON - Secretaria</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
    <style>
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include '../partials/_navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <?php include '../partials/_sidebar.php'; ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">
                            <span class="page-title-icon bg-gradient-primary text-white me-2">
                                <i class="mdi mdi-code-json"></i>
                            </span>
                            Exportar Dados (JSON)
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Secretaria</a></li>
                                <li class="breadcrumb-item"><a href="index.php">Pré-cadastros</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Exportar JSON</li>
                            </ul>
                        </nav>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Ações em Lote</h4>
                                    <!-- Alterado para chamar função JS de cópia -->
                                    <button onclick="copiarJSON(0, true)" class="btn btn-gradient-info btn-lg">
                                        <i class="mdi mdi-content-copy"></i> Copiar JSON de Todos os Alunos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Selecione para copiar individualmente</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>CPF</th>
                                                    <th>Turma</th>
                                                    <th>Status</th>
                                                    <th>Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pre_cadastros as $aluno): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                                        <td><?php echo htmlspecialchars($aluno['cpf'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não definida'); ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_class = 'badge-warning';
                                                            if ($aluno['status_cadastro'] === 'completo')
                                                                $status_class = 'badge-info';
                                                            if ($aluno['status_cadastro'] === 'aprovado')
                                                                $status_class = 'badge-success';
                                                            ?>
                                                            <label class="badge <?php echo $status_class; ?>">
                                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $aluno['status_cadastro']))); ?>
                                                            </label>
                                                        </td>
                                                        <td>
                                                            <!-- Alterado para chamar função JS de cópia -->
                                                            <button onclick="copiarJSON(<?php echo $aluno['id']; ?>, false)"
                                                                class="btn btn-inverse-primary btn-sm">
                                                                <i class="mdi mdi-content-copy"></i> Copiar
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de Sucesso -->
    <div class="toast-container">
        <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="mdi mdi-check-circle"></i> JSON copiado para a área de transferência!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>

    <script>
        function copiarJSON(alunoId, todos) {
            // Mostrar indicador de carregamento (opcional, pode ser um toast simples)
            const btnText = todos ? 'Copiando...' : '...';

            // Fazer requisição AJAX
            $.ajax({
                url: 'gerar_json.php',
                type: 'POST',
                data: {
                    acao: 'obter_json',
                    aluno_id: alunoId,
                    todos: todos ? 1 : 0
                },
                dataType: 'json',
                success: function (response) {
                    if (response.sucesso) {
                        const jsonString = JSON.stringify(response.dados, null, 2);

                        // Copiar para clipboard
                        navigator.clipboard.writeText(jsonString).then(function () {
                            // Mostrar Toast de Sucesso
                            const toastEl = document.getElementById('toastSuccess');
                            const toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        }, function (err) {
                            alert('Erro ao copiar para a área de transferência: ' + err);
                        });
                    } else {
                        alert('Erro ao obter dados: ' + response.erro);
                    }
                },
                error: function (xhr, status, error) {
                    alert('Erro na requisição: ' + error);
                }
            });
        }
    </script>
</body>

</html>