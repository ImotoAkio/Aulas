<?php
// dashboard_notas.php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/config/database.php';
    redirectTo('login.php');
    exit();
}

// Buscar todas as turmas
$turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as disciplinas
$disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pedagógico</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Dashboard Pedagógico</h2>
    <form method="GET" class="row mb-4">
        <div class="col-md-4">
            <label>Turma</label>
            <select name="turma_id" class="form-control" required>
                <option value="">Selecione</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($_GET['turma_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= $t['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Disciplina</label>
            <select name="disciplina_id" class="form-control" required>
                <option value="">Selecione</option>
                <?php foreach ($disciplinas as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($_GET['disciplina_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= $d['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Visualizar</button>
        </div>
    </form>

<?php if (!empty($_GET['turma_id']) && !empty($_GET['disciplina_id'])): 
    $turma_id = $_GET['turma_id'];
    $disciplina_id = $_GET['disciplina_id'];
    $alunos = $pdo->prepare("SELECT a.id, a.nome FROM alunos a WHERE a.turma_id = ?");
    $alunos->execute([$turma_id]);
    $alunos = $alunos->fetchAll(PDO::FETCH_ASSOC);

    $dados = [];
    foreach ($alunos as $a) {
        $stmt = $pdo->prepare("SELECT media_1, media_2, media_3, media_4 FROM notas WHERE aluno_id = ? AND turma_id = ? AND disciplina_id = ?");
        $stmt->execute([$a['id'], $turma_id, $disciplina_id]);
        $notas = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($notas) {
            $dados[] = [
                'id' => $a['id'], // Adicione o ID do aluno aqui
                'nome' => $a['nome'],
                'media_1' => $notas['media_1'],
                'media_2' => $notas['media_2'],
                'media_3' => $notas['media_3'],
                'media_4' => $notas['media_4'],
            ];
        }
    }
?>
    <h4 class="mt-5">Notas por Aluno</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Aluno</th>
                <th>1ª Unidade</th>
                <th>2ª Unidade</th>
                <th>3ª Unidade</th>
                <th>4ª Unidade</th>
                <th>Média Final</th>
                <th>Boletim</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dados as $d): 
                $medias = array_filter([$d['media_1'], $d['media_2'], $d['media_3'], $d['media_4']], fn($v) => $v !== null);
                $media_final = count($medias) ? array_sum($medias) / count($medias) : '-';
            ?>
            <tr>
                <td><?= $d['nome'] ?></td>
                <td><?= $d['media_1'] ?? '-' ?></td>
                <td><?= $d['media_2'] ?? '-' ?></td>
                <td><?= $d['media_3'] ?? '-' ?></td>
                <td><?= $d['media_4'] ?? '-' ?></td>
                <td><?= is_numeric($media_final) ? number_format($media_final, 2) : '-' ?></td>
                <td>
                    <!-- Passar o ID correto de cada aluno -->
                    <a href="gerar_boletim.php?aluno_id=<?= $d['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Boletim PDF</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-5">Gráfico de Médias Finais</h4>
    <canvas id="graficoMedias"></canvas>
    <script>
        const ctx = document.getElementById('graficoMedias').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($dados, 'nome')) ?>,
                datasets: [{
                    label: 'Média Final',
                    data: <?= json_encode(array_map(function($d) {
                        $m = array_filter([$d['media_1'], $d['media_2'], $d['media_3'], $d['media_4']], fn($v) => $v !== null);
                        return count($m) ? round(array_sum($m)/count($m), 2) : 0;
                    }, $dados)) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });
    </script>

    <!-- Novo botão para baixar todos os boletins -->
    <div class="text-center mt-4">
        <a href="gerar_boletins_turma.php?turma_id=<?= $turma_id ?>&disciplina_id=<?= $disciplina_id ?>" class="btn btn-success">Baixar Boletins de Todos</a>
    </div>

<?php endif; ?>
</div>
</body>
</html>
