<?php
include 'partials/session.php';
require 'db.php'; // Garanta que $pdo está disponível

// Função para buscar dados com tratamento de erro básico
function fetchData($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // error_log("Database Error: " . $e->getMessage());
        return null;
    }
}

// --- Busca de Dados Iniciais para Filtros ---
$turmas = fetchData($pdo, "SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC) ?? [];
$unidades = fetchData($pdo, "SELECT DISTINCT unidade FROM notas WHERE unidade IS NOT NULL AND unidade != '' ORDER BY unidade")->fetchAll(PDO::FETCH_ASSOC) ?? [];
$disciplinas = fetchData($pdo, "SELECT id, nome FROM disciplinas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC) ?? [];

// --- Processamento dos Filtros ---
$turma_selecionada_id = filter_input(INPUT_GET, 'turma', FILTER_SANITIZE_NUMBER_INT);
$unidade_selecionada = filter_input(INPUT_GET, 'unidade', FILTER_SANITIZE_STRING);

$alunos_da_turma = [];
$notas_organizadas = []; // Array para [aluno_id][disciplina_id] => media_1

if ($turma_selecionada_id && $unidade_selecionada) {
    // 1. Buscar alunos da turma
    $stmt_alunos = fetchData($pdo, "SELECT id, nome FROM alunos WHERE turma_id = :turma_id ORDER BY nome", ['turma_id' => $turma_selecionada_id]);
    $alunos_da_turma = $stmt_alunos ? $stmt_alunos->fetchAll(PDO::FETCH_ASSOC) : [];

    // 2. Buscar notas (media_1) da turma/unidade
    $sql_notas = "SELECT aluno_id, disciplina_id, media_1
                  FROM notas
                  WHERE turma_id = :turma_id AND unidade = :unidade";
    $stmt_notas = fetchData($pdo, $sql_notas, [
        'turma_id' => $turma_selecionada_id,
        'unidade' => $unidade_selecionada
    ]);
    $notas_raw = $stmt_notas ? $stmt_notas->fetchAll(PDO::FETCH_ASSOC) : [];

    // 3. Organizar as notas (media_1)
    foreach ($notas_raw as $nota) {
        if (isset($nota['media_1']) && $nota['media_1'] !== null && is_numeric($nota['media_1'])) {
             $notas_organizadas[$nota['aluno_id']][$nota['disciplina_id']] = round((float)$nota['media_1'], 1);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php
    $title = "Boletim da Turma";
    include 'partials/title-meta.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
        .nota-baixa { color: red; font-weight: bold; }
        .nota-alta { color: green; font-weight: bold; }
        .table th, .table td { vertical-align: middle; text-align: center; }
        .table th.disciplina-header { writing-mode: vertical-lr; transform: rotate(180deg); white-space: nowrap; padding-bottom: 10px; padding-top: 10px; cursor: default; }
        .table td.aluno-nome { text-align: left; }
        .table-sm th, .table-sm td { padding: 0.4rem; }
    </style>
</head>
<body id="body" class="dark-sidebar">
    <?php include 'partials/menu.php'; ?>

    <div class="page-wrapper">
        <div class="page-content-tab">
            <div class="container-fluid">
                <!-- Page-Title -->
                <?php
                $page_title = "Boletim da Turma";
                $sub_title = "Pedagógico";
                include 'partials/page-title.php';
                ?>

                <!-- Card de Filtros -->
                 <div class="row">
                    <div class="col-12">
                         <div class="card">
                            <div class="card-header"><h4 class="card-title">Selecionar Turma e Unidade</h4></div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <div class="row">
                                        <div class="col-md-5 mb-3">
                                            <label for="turma" class="form-label">Turma</label>
                                            <select class="form-select" id="turma" name="turma" required>
                                                <option value="">--- Selecione ---</option>
                                                <?php foreach ($turmas as $t): ?>
                                                    <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($turma_selecionada_id == $t['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($t['nome']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <label for="unidade" class="form-label">Unidade/Bimestre</label>
                                            <select class="form-select" id="unidade" name="unidade" required>
                                                <option value="">--- Selecione ---</option>
                                                <?php foreach ($unidades as $u): ?>
                                                    <option value="<?= htmlspecialchars($u['unidade']) ?>" <?= ($unidade_selecionada == $u['unidade']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($u['unidade']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3 align-self-end">
                                            <button class="btn btn-primary w-100" type="submit"><i class="las la-search"></i> Ver Boletim</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Notas (Boletim) -->
                <?php if ($turma_selecionada_id && $unidade_selecionada): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        Boletim - Turma:
                                        <?php $nome_turma = ''; foreach($turmas as $t){ if($t['id'] == $turma_selecionada_id) {$nome_turma = $t['nome']; break;}} echo htmlspecialchars($nome_turma); ?>
                                        - Unidade: <?= htmlspecialchars($unidade_selecionada) ?>
                                    </h4>
                                     <!-- Adicionar botão de impressão -->
                                    <div class="float-end">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="window.print();"><i class="fas fa-print me-1"></i> Imprimir</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($alunos_da_turma)): ?>
                                        <div class="alert alert-warning">Nenhum aluno encontrado para esta turma.</div>
                                    <?php elseif (empty($disciplinas)): ?>
                                        <div class="alert alert-warning">Nenhuma disciplina cadastrada.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover table-sm mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="text-align: left; vertical-align: bottom;">Aluno</th>
                                                        <?php foreach ($disciplinas as $disciplina): ?>
                                                            <th class="disciplina-header" title="<?= htmlspecialchars($disciplina['nome']) ?>">
                                                                <?= htmlspecialchars($disciplina['nome']) ?>
                                                            </th>
                                                        <?php endforeach; ?>
                                                        <th class="disciplina-header">Média<br>Geral<br>Unidade</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($alunos_da_turma as $aluno): ?>
                                                        <?php
                                                            $soma_medias_aluno = 0;
                                                            $count_medias_aluno = 0;
                                                        ?>
                                                        <tr>
                                                            <td class="aluno-nome"><?= htmlspecialchars($aluno['nome']) ?></td>
                                                            <?php foreach ($disciplinas as $disciplina): ?>
                                                                <?php
                                                                    $nota_unidade_disciplina = $notas_organizadas[$aluno['id']][$disciplina['id']] ?? null;

                                                                    if ($nota_unidade_disciplina !== null) {
                                                                        $soma_medias_aluno += $nota_unidade_disciplina;
                                                                        $count_medias_aluno++;
                                                                    }

                                                                    $classe_nota = '';
                                                                    if ($nota_unidade_disciplina !== null) {
                                                                        if ($nota_unidade_disciplina < 6.0) $classe_nota = 'nota-baixa';
                                                                        elseif ($nota_unidade_disciplina >= 9.0) $classe_nota = 'nota-alta';
                                                                    }
                                                                ?>
                                                                <td class="<?= $classe_nota ?>">
                                                                    <?= ($nota_unidade_disciplina !== null) ? number_format($nota_unidade_disciplina, 1, ',', '.') : '-' ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <?php
                                                                $media_geral_aluno_unidade = ($count_medias_aluno > 0) ? round($soma_medias_aluno / $count_medias_aluno, 1) : null;
                                                                $classe_media_geral = '';
                                                                if ($media_geral_aluno_unidade !== null) {
                                                                    if ($media_geral_aluno_unidade < 6.0) $classe_media_geral = 'nota-baixa';
                                                                    elseif ($media_geral_aluno_unidade >= 9.0) $classe_media_geral = 'nota-alta';
                                                                }
                                                            ?>
                                                            <td class="<?= $classe_media_geral ?>" style="font-weight: bold;">
                                                                <?= ($media_geral_aluno_unidade !== null) ? number_format($media_geral_aluno_unidade, 1, ',', '.') : '-' ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- container -->
            <?php include 'partials/right-sidebar.php'; ?>
            <?php include 'partials/footer.php'; ?>
        </div><!-- end page content -->
    </div><!-- end page-wrapper -->

    <!-- Javascript -->
    <!-- NENHUM JS de gráfico necessário -->
    <script src="assets/js/app.js"></script>

</body>
</html>