<?php
include 'partials/session.php';
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/config/database.php';
    redirectTo('login.php');
    exit;
}

$feedback_message = ''; // Variável para armazenar mensagens de feedback

// Processa as ações POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $turma = $_POST['turma'];
        $disciplina = $_POST['disciplina'];
        $data = $_POST['data'];
        $conteudo = $_POST['conteudo'];
        $objetivos = $_POST['objetivos'];
        $metodologia = $_POST['metodologia'];
        $recursos = $_POST['recursos'];
        $metodo_avaliativo = $_POST['metodo_avaliativo'];

        // Validação básica (poderia ser mais robusta)
        if (empty($turma) || empty($disciplina) || empty($data) || empty($conteudo) || empty($objetivos) || empty($metodologia) || empty($recursos) || empty($metodo_avaliativo)) {
            $feedback_message = "<div class='alert alert-danger'>Todos os campos são obrigatórios para edição.</div>";
        } else {
            $stmt = $pdo->prepare("UPDATE planos_aula SET turma = :turma, disciplina = :disciplina, data = :data, conteudo = :conteudo, objetivos = :objetivos, metodologia = :metodologia, recursos = :recursos, metodo_avaliativo = :metodo_avaliativo WHERE id = :id");
            $stmt->execute([
                'turma' => $turma,
                'disciplina' => $disciplina,
                'data' => $data,
                'conteudo' => $conteudo,
                'objetivos' => $objetivos,
                'metodologia' => $metodologia,
                'recursos' => $recursos,
                'metodo_avaliativo' => $metodo_avaliativo,
                'id' => $id
            ]);
            $feedback_message = "<div class='alert alert-success'>Plano de aula atualizado com sucesso!</div>";
        }
    } elseif (isset($_POST['aprovar'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'aprovado' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $feedback_message = "<div class='alert alert-success'>Plano de aula aprovado com sucesso!</div>";
    } elseif (isset($_POST['marcar_revisao'])) {
        $id = $_POST['id'];
        $mensagem_revisao = $_POST['mensagem_revisao'];

        if (empty($mensagem_revisao)) {
            $feedback_message = "<div class='alert alert-danger'>A mensagem de revisão é obrigatória.</div>";
        } else {
            $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'revisao', mensagem_revisao = :mensagem_revisao WHERE id = :id");
            $stmt->execute(['mensagem_revisao' => $mensagem_revisao, 'id' => $id]);
            $feedback_message = "<div class='alert alert-warning'>Plano de aula marcado para revisão.</div>";
        }
    }
}

// Busca os planos pendentes - CORRIGIDO PARA BUSCAR TODOS OS CAMPOS
$stmt = $pdo->prepare("
    SELECT pa.*, u.nome AS professor
    FROM planos_aula pa
    JOIN usuarios u ON pa.professor_id = u.id
    WHERE pa.status = 'pendente' OR pa.status = 'revisao' -- Opcional: Mostrar também os marcados para revisão aqui
    ORDER BY pa.data DESC, pa.id DESC
");
$stmt->execute();
$planos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-br"> <!-- CORRIGIDO lang -->

<head>
    <?php $title = "Gerenciar Planos de Aula"; include 'partials/title-meta.php'; ?>
    <?php include 'partials/head-css.php'; ?>
</head>

<body id="body" class="dark-sidebar">
    <?php include 'partials/menu.php'; ?>

    <div class="page-wrapper">
        <div class="page-content-tab">
            <div class="container-fluid">
                <!-- Page-Title -->
                <?php
                $page_title = "Gerenciar Planos de Aula"; // CORRIGIDO
                $sub_title = "Pedagógico";    // CORRIGIDO
                include 'partials/page-title.php'; ?>
                <!-- end page title end breadcrumb -->

                <?php
                // Exibe a mensagem de feedback, se houver
                if (!empty($feedback_message)) {
                    echo $feedback_message;
                }
                ?>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Planos de Aula Pendentes ou em Revisão</h4>
                                <p class="text-muted mb-0">
                                    Visualize, edite, aprove ou solicite revisão dos planos de aula.
                                </p>
                            </div><!--end card-header-->
                            <div class="card-body">
                                <?php if (empty($planos)): ?>
                                    <div class="alert alert-info">Nenhum plano de aula pendente ou para revisão no momento.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Id</th>
                                                    <th>Professor</th>
                                                    <th>Turma</th>
                                                    <th>Disciplina</th>
                                                    <th>Data</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($planos as $plano): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($plano['id']) ?></td>
                                                        <td><?= htmlspecialchars($plano['professor']) ?></td>
                                                        <td><?= htmlspecialchars($plano['turma']) ?></td>
                                                        <td><?= htmlspecialchars($plano['disciplina']) ?></td>
                                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($plano['data']))) // Formatar data ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php
                                                                switch ($plano['status']) {
                                                                    case 'pendente': echo 'warning'; break;
                                                                    case 'aprovado': echo 'success'; break;
                                                                    case 'revisao': echo 'danger'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?>">
                                                                <?= htmlspecialchars(ucfirst($plano['status'])) ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <!-- Botão Visualizar -->
                                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#visualizarModal<?= $plano['id'] ?>"><i class="fas fa-eye"></i></button>

                                                            <!-- Botão Editar -->
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?= $plano['id'] ?>"><i class="las la-pen"></i></button>

                                                            <!-- Botão Aprovar (só se pendente ou revisão) -->
                                                            <?php if ($plano['status'] == 'pendente' || $plano['status'] == 'revisao'): ?>
                                                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#aprovarModal<?= $plano['id'] ?>"><i class="fas fa-check"></i></button>
                                                            <?php endif; ?>

                                                            <!-- Botão Marcar para Revisão (só se pendente) -->
                                                            <?php if ($plano['status'] == 'pendente'): ?>
                                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#revisaoModal<?= $plano['id'] ?>"><i class="fas fa-sync-alt"></i></button>
                                                            <?php endif; ?>


                                                            <!-- Modal Aprovar -->
                                                            <div class="modal fade" id="aprovarModal<?= $plano['id'] ?>" tabindex="-1" aria-labelledby="aprovarModalLabel<?= $plano['id'] ?>" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="aprovarModalLabel<?= $plano['id'] ?>">Confirmar Aprovação</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body text-start">
                                                                            Tem certeza que deseja aprovar o plano de aula de <strong><?= htmlspecialchars($plano['disciplina']) ?></strong> para a turma <strong><?= htmlspecialchars($plano['turma']) ?></strong> do professor(a) <strong><?= htmlspecialchars($plano['professor']) ?></strong>?
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <form method="POST" action="">
                                                                                <input type="hidden" name="id" value="<?= $plano['id'] ?>">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                                <button type="submit" name="aprovar" class="btn btn-success">Aprovar</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Modal Editar -->
                                                            <div class="modal fade" id="editarModal<?= $plano['id'] ?>" tabindex="-1" aria-labelledby="editarModalLabel<?= $plano['id'] ?>" aria-hidden="true">
                                                                <div class="modal-dialog modal-lg"> <!-- modal-lg para mais espaço -->
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="editarModalLabel<?= $plano['id'] ?>">Editar Plano de Aula</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body text-start">
                                                                            <form method="POST" action="">
                                                                                <input type="hidden" name="id" value="<?= $plano['id'] ?>">
                                                                                <div class="row">
                                                                                    <div class="col-md-6 mb-3">
                                                                                        <label for="turma<?= $plano['id'] ?>" class="form-label">Turma</label>
                                                                                        <input type="text" class="form-control" id="turma<?= $plano['id'] ?>" name="turma" value="<?= htmlspecialchars($plano['turma']) ?>" required>
                                                                                    </div>
                                                                                    <div class="col-md-6 mb-3">
                                                                                        <label for="disciplina<?= $plano['id'] ?>" class="form-label">Disciplina</label>
                                                                                        <input type="text" class="form-control" id="disciplina<?= $plano['id'] ?>" name="disciplina" value="<?= htmlspecialchars($plano['disciplina']) ?>" required>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="data<?= $plano['id'] ?>" class="form-label">Data</label>
                                                                                    <input type="date" class="form-control" id="data<?= $plano['id'] ?>" name="data" value="<?= htmlspecialchars($plano['data']) ?>" required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="conteudo<?= $plano['id'] ?>" class="form-label">Conteúdo</label>
                                                                                    <textarea class="form-control" id="conteudo<?= $plano['id'] ?>" name="conteudo" rows="3" required><?= htmlspecialchars($plano['conteudo']) ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="objetivos<?= $plano['id'] ?>" class="form-label">Objetivos</label>
                                                                                    <textarea class="form-control" id="objetivos<?= $plano['id'] ?>" name="objetivos" rows="3" required><?= htmlspecialchars($plano['objetivos']) ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="metodologia<?= $plano['id'] ?>" class="form-label">Metodologia</label>
                                                                                    <textarea class="form-control" id="metodologia<?= $plano['id'] ?>" name="metodologia" rows="3" required><?= htmlspecialchars($plano['metodologia']) ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="recursos<?= $plano['id'] ?>" class="form-label">Recursos</label>
                                                                                    <textarea class="form-control" id="recursos<?= $plano['id'] ?>" name="recursos" rows="3" required><?= htmlspecialchars($plano['recursos']) ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="metodo_avaliativo<?= $plano['id'] ?>" class="form-label">Método Avaliativo</label>
                                                                                    <textarea class="form-control" id="metodo_avaliativo<?= $plano['id'] ?>" name="metodo_avaliativo" rows="3" required><?= htmlspecialchars($plano['metodo_avaliativo']) ?></textarea>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                                    <button type="submit" name="editar" class="btn btn-primary">Salvar Alterações</button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Modal Revisão -->
                                                            <div class="modal fade" id="revisaoModal<?= $plano['id'] ?>" tabindex="-1" aria-labelledby="revisaoModalLabel<?= $plano['id'] ?>" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="revisaoModalLabel<?= $plano['id'] ?>">Marcar Plano para Revisão</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body text-start">
                                                                            <form method="POST" action="">
                                                                                <input type="hidden" name="id" value="<?= $plano['id'] ?>">
                                                                                <p>Plano de Aula: <strong><?= htmlspecialchars($plano['disciplina']) ?></strong> - Turma: <strong><?= htmlspecialchars($plano['turma']) ?></strong></p>
                                                                                <div class="mb-3">
                                                                                    <label for="mensagem_revisao<?= $plano['id'] ?>" class="form-label">Mensagem de Revisão para o Professor</label>
                                                                                    <textarea class="form-control" id="mensagem_revisao<?= $plano['id'] ?>" name="mensagem_revisao" rows="3" required><?= htmlspecialchars($plano['mensagem_revisao'] ?? '') ?></textarea>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                                    <button type="submit" name="marcar_revisao" class="btn btn-warning">Enviar para Revisão</button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Modal Visualizar -->
                                                            <div class="modal fade" id="visualizarModal<?= $plano['id'] ?>" tabindex="-1" aria-labelledby="visualizarModalLabel<?= $plano['id'] ?>" aria-hidden="true">
                                                                <div class="modal-dialog modal-lg"> <!-- modal-lg para mais espaço -->
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="visualizarModalLabel<?= $plano['id'] ?>">Detalhes do Plano de Aula</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body text-start">
                                                                            <p><strong>ID:</strong> <?= htmlspecialchars($plano['id']) ?></p>
                                                                            <p><strong>Professor:</strong> <?= htmlspecialchars($plano['professor']) ?></p>
                                                                            <p><strong>Turma:</strong> <?= htmlspecialchars($plano['turma']) ?></p>
                                                                            <p><strong>Disciplina:</strong> <?= htmlspecialchars($plano['disciplina']) ?></p>
                                                                            <p><strong>Data:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($plano['data']))) ?></p>
                                                                            <p><strong>Status:</strong> <span class="badge bg-<?php
                                                                                switch ($plano['status']) {
                                                                                    case 'pendente': echo 'warning'; break;
                                                                                    case 'aprovado': echo 'success'; break;
                                                                                    case 'revisao': echo 'danger'; break;
                                                                                    default: echo 'secondary';
                                                                                }
                                                                            ?>"><?= htmlspecialchars(ucfirst($plano['status'])) ?></span></p>
                                                                            <hr>
                                                                            <p><strong>Conteúdo:</strong></p>
                                                                            <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6;"><?= nl2br(htmlspecialchars($plano['conteudo'])) ?></div>
                                                                            <hr>
                                                                            <p><strong>Objetivos:</strong></p>
                                                                            <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6;"><?= nl2br(htmlspecialchars($plano['objetivos'])) ?></div>
                                                                            <hr>
                                                                            <p><strong>Metodologia:</strong></p>
                                                                            <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6;"><?= nl2br(htmlspecialchars($plano['metodologia'])) ?></div>
                                                                            <hr>
                                                                            <p><strong>Recursos:</strong></p>
                                                                            <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6;"><?= nl2br(htmlspecialchars($plano['recursos'])) ?></div>
                                                                            <hr>
                                                                            <p><strong>Método Avaliativo:</strong></p>
                                                                            <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6;"><?= nl2br(htmlspecialchars($plano['metodo_avaliativo'])) ?></div>
                                                                            <?php if ($plano['status'] == 'revisao' && !empty($plano['mensagem_revisao'])): ?>
                                                                            <hr>
                                                                            <p><strong>Mensagem de Revisão:</strong></p>
                                                                            <div style="white-space: pre-wrap; background-color: #fff3cd; color: #664d03; padding: 10px; border-radius: 4px; border: 1px solid #ffecb5;"><?= nl2br(htmlspecialchars($plano['mensagem_revisao'])) ?></div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table><!--end -->
                                    </div><!--end table-responsive-->
                                <?php endif; ?>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div> <!-- end col -->
                </div> <!-- end row -->
            </div><!-- container -->

            <!--Start Rightbar-->
            <?php include 'partials/right-sidebar.php'; ?>
            <!--end Rightbar-->

            <!--Start Footer-->
            <?php include 'partials/footer.php'; ?>
            <!--end footer-->
        </div>
    </div>

    <!-- Javascript -->
    <!-- Vendor JS (necessário para Modals do Bootstrap) -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>