<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getPageUrl')) {
  require_once __DIR__ . '/../../config/database.php';
}

session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
  require_once __DIR__ . '/../../config/database.php';
  redirectTo('login.php');
  exit();
}

// Buscar alunos com informações de turma
$alunos = [];
try {
  $stmt = $pdo->query("
        SELECT a.*, t.nome as turma_nome, t.ano_letivo 
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        ORDER BY a.nome_completo, a.nome
    ");
  $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
  error_log("Erro ao buscar alunos: " . $e->getMessage());
}

// Buscar turmas para o filtro
$turmas_filtro = [];
try {
  $stmt = $pdo->query("SELECT id, nome, ano_letivo FROM turmas ORDER BY ano_letivo DESC, nome");
  $turmas_filtro = $stmt->fetchAll();
} catch (PDOException $e) {
  error_log("Erro ao buscar turmas: " . $e->getMessage());
}

// Contar alunos com cadastro completo vs incompleto
$total_alunos = count($alunos);
$alunos_completos = 0;
$alunos_incompletos = 0;

foreach ($alunos as $aluno) {
  if (!empty($aluno['nome_completo']) && !empty($aluno['data_nascimento']) && !empty($aluno['cpf'])) {
    $alunos_completos++;
  } else {
    $alunos_incompletos++;
  }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Listar Alunos</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/datatables/dataTables.bootstrap4.css"); ?>">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>" />

  <style>
    .table td {
      vertical-align: middle;
    }

    .btn-group .btn {
      margin-right: 2px;
    }

    .btn-group .btn:last-child {
      margin-right: 0;
    }

    .badge {
      font-size: 0.75rem;
    }

    .table tbody tr:hover {
      background-color: rgba(0, 0, 0, 0.05);
    }

    /* Estilos para a barra de pesquisa */
    .input-group-text {
      background-color: #f8f9fa;
      border-color: #ced4da;
    }

    #buscarAluno {
      border-left: none;
    }

    #buscarAluno:focus {
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    #limparBusca {
      border-left: none;
    }

    #limparBusca:hover {
      background-color: #e9ecef;
    }

    #contadorResultados {
      font-weight: 500;
    }
  </style>
</head>

<body>
  <div class="container-scroller">
    <!-- partial:../partials/_navbar.html -->
    <?php include '../partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../partials/_sidebar.html -->
      <?php include '../partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Listar Alunos </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href='<?php echo getPageUrl("secretaria/index.php"); ?>'>Dashboard</a>
                </li>
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Listar Alunos</li>
              </ol>
            </nav>
          </div>

          <!-- Estatísticas -->
          <div class="row">
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $total_alunos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-account-multiple icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Total de Alunos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $alunos_completos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-success">
                        <span class="mdi mdi-check-circle icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Cadastros Completos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0"><?= $alunos_incompletos ?></h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-warning">
                        <span class="mdi mdi-alert icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Cadastros Incompletos</h6>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-9">
                      <div class="d-flex align-items-center align-self-start">
                        <h3 class="mb-0">
                          <?= $total_alunos > 0 ? round(($alunos_completos / $total_alunos) * 100, 1) : 0 ?>%</h3>
                      </div>
                    </div>
                    <div class="col-3">
                      <div class="icon icon-box-info">
                        <span class="mdi mdi-percent icon-item"></span>
                      </div>
                    </div>
                  </div>
                  <h6 class="text-muted font-weight-normal">Taxa de Completude</h6>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabela de Alunos -->
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                      <h4 class="card-title">Lista de Alunos</h4>
                      <p class="card-description">Gerencie os cadastros dos alunos. Clique em "Editar" para completar
                        informações faltantes.</p>
                    </div>

                    <div class="d-flex align-items-center">
                      <div class="mr-3">
                        <select class="form-control form-control-sm" id="filtroTurma" style="width: 200px;">
                          <option value="">Todas as Turmas</option>
                          <option value="Sem turma">Sem Turma</option>
                          <?php foreach ($turmas_filtro as $t): ?>
                            <option value="<?= htmlspecialchars($t['nome']) ?>"><?= htmlspecialchars($t['nome']) ?>
                              (<?= $t['ano_letivo'] ?>)</option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mr-3">
                        <select class="form-control form-control-sm" id="filtroStatus" style="width: 150px;">
                          <option value="">Todos os Status</option>
                          <option value="Completo">Completo</option>
                          <option value="Incompleto">Incompleto</option>
                        </select>
                      </div>
                      <div class="input-group" style="width: 300px;">
                        <div class="input-group-prepend">
                          <span class="input-group-text">
                            <i class="mdi mdi-magnify"></i>
                          </span>
                        </div>
                        <input type="text" class="form-control" id="buscarAluno"
                          placeholder="Buscar por nome, CPF ou turma...">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary" type="button" id="limparBusca" title="Limpar busca">
                            <i class="mdi mdi-close"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Contador de resultados -->
                  <div class="mb-3">
                    <small class="text-muted">
                      <span id="contadorResultados"><?= $total_alunos ?></span> aluno(s) encontrado(s)
                    </small>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-striped" id="tabelaAlunos">
                      <thead>
                        <tr>
                          <th>Nome</th>
                          <th>Turma</th>
                          <th>Status</th>
                          <th>Ações</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                          <tr>
                            <td>
                              <div class="d-flex align-items-center">
                                <div class="mr-3">
                                  <i class="mdi mdi-account-circle text-primary" style="font-size: 24px;"></i>
                                </div>
                                <div>
                                  <h6 class="mb-0"><?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?></h6>
                                  <small class="text-muted">
                                    <?php if (!empty($aluno['cpf'])): ?>
                                      CPF: <?= htmlspecialchars($aluno['cpf']) ?>
                                    <?php else: ?>
                                      CPF não informado
                                    <?php endif; ?>
                                  </small>
                                </div>
                              </div>
                            </td>
                            <td>
                              <div>
                                <span
                                  class="font-weight-bold"><?= htmlspecialchars($aluno['turma_nome'] ?? 'Sem turma') ?></span>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($aluno['ano_letivo'] ?? 'N/A') ?></small>
                              </div>
                            </td>
                            <td>
                              <?php if (!empty($aluno['nome_completo']) && !empty($aluno['data_nascimento']) && !empty($aluno['cpf'])): ?>
                                <span class="badge badge-success">
                                  <i class="mdi mdi-check-circle"></i> Completo
                                </span>
                              <?php else: ?>
                                <span class="badge badge-warning">
                                  <i class="mdi mdi-alert"></i> Incompleto
                                </span>
                              <?php endif; ?>
                              <?php if (empty($aluno['nome_completo'])): ?>
                                <br><small class="text-muted">Nome antigo</small>
                              <?php endif; ?>
                            </td>
                            <td>
                              <div class="btn-group" role="group">
                                <a href="<?php echo getPageUrl('secretaria/cad/editar_aluno.php?id=' . $aluno['id']); ?>"
                                  class="btn btn-outline-primary btn-sm" title="Editar">
                                  <i class="mdi mdi-pencil"></i>
                                </a>
                                <a href="<?php echo getPageUrl('secretaria/cad/visualizar_aluno.php?id=' . $aluno['id']); ?>"
                                  class="btn btn-outline-info btn-sm" title="Visualizar">
                                  <i class="mdi mdi-eye"></i>
                                </a>
                                <a href="<?php echo getPageUrl('secretaria/cad/excluir_aluno.php?id=' . $aluno['id']); ?>"
                                  class="btn btn-outline-danger btn-sm" title="Excluir"
                                  onclick="return confirm('Tem certeza que deseja excluir este aluno?')">
                                  <i class="mdi mdi-delete"></i>
                                </a>
                              </div>
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
        <!-- content-wrapper ends -->
        <!-- partial:../../partials/_footer.html -->
        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2023 <a
                href="https://www.bootstrapdash.com/" target="_blank">BootstrapDash</a>. All rights reserved.</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i
                class="mdi mdi-heart text-danger"></i></span>
          </div>
        </footer>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- jQuery primeiro -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- plugins:js -->
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <!-- endinject -->
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Plugin js for this page -->
  <!-- DataTables via CDN -->
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="<?php echo getAssetUrl("assets/js/data-table.js"); ?>"></script>
  <!-- End custom js for this page -->

  <script>
    // Aguardar carregamento completo da página
    $(document).ready(function () {
      console.log('Página carregada, inicializando...');

      // Verificar se jQuery está funcionando
      if (typeof $ === 'undefined') {
        console.error('jQuery não carregado!');
        // Usar busca fallback
        buscaFallback();
        return;
      }

      // Verificar se DataTable está disponível
      if (typeof $.fn.DataTable === 'undefined') {
        console.error('DataTable não carregado!');
        // Usar busca fallback
        buscaFallback();
        return;
      }

      console.log('jQuery e DataTable carregados, inicializando tabela...');

      try {
        // Inicializar DataTable
        var table = $('#tabelaAlunos').DataTable({
          "language": {
            "sEmptyTable": "Nenhum registro encontrado",
            "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
            "sInfoFiltered": "(Filtrados de _MAX_ registros)",
            "sInfoPostFix": "",
            "sInfoThousands": ".",
            "sLengthMenu": "_MENU_ resultados por página",
            "sLoadingRecords": "Carregando...",
            "sProcessing": "Processando...",
            "sZeroRecords": "Nenhum registro encontrado",
            "sSearch": "Pesquisar:",
            "oPaginate": {
              "sNext": "Próximo",
              "sPrevious": "Anterior",
              "sFirst": "Primeiro",
              "sLast": "Último"
            },
            "oAria": {
              "sSortAscending": ": Ordenar colunas de forma ascendente",
              "sSortDescending": ": Ordenar colunas de forma descendente"
            }
          },
          "pageLength": 20,
          "order": [[0, "asc"]],
          "columnDefs": [
            { "orderable": false, "targets": 3 } // Desabilitar ordenação na coluna de ações
          ],
          "responsive": true,
          "dom": '<"top"f>rt<"bottom"lip><"clear">',
          "lengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]]
        });

        console.log('DataTable inicializado com sucesso');

        // Função de busca personalizada
        function buscarAlunos(termo) {
          console.log('Buscando por:', termo);

          if (termo.length === 0) {
            // Se não há termo, mostrar todos
            table.search('').draw();
          } else {
            // Buscar em todas as colunas (nome, CPF, turma)
            table.search(termo).draw();
          }

          // Atualizar contador de resultados
          var totalFiltrado = table.rows({ search: 'applied' }).count();
          $('#contadorResultados').text(totalFiltrado);

          // Mostrar mensagem se não houver resultados
          if (totalFiltrado === 0 && termo.length > 0) {
            $('#contadorResultados').html('<span class="text-warning">Nenhum aluno encontrado para "' + termo + '"</span>');
          }

          console.log('Resultados encontrados:', totalFiltrado);
        }

        // Event listener para o campo de busca
        $('#buscarAluno').on('keyup', function () {
          var termo = $(this).val();
          buscarAlunos(termo);
        });

        // Event listeners para os filtros
        $('#filtroTurma').on('change', function () {
          var val = $(this).val();
          // Coluna 1 é a Turma
          table.column(1).search(val ? val : '', true, false).draw();

          // Atualizar contador
          var totalFiltrado = table.rows({ search: 'applied' }).count();
          $('#contadorResultados').text(totalFiltrado);
        });

        $('#filtroStatus').on('change', function () {
          var val = $(this).val();
          // Coluna 2 é o Status
          table.column(2).search(val ? val : '', true, false).draw();

          // Atualizar contador
          var totalFiltrado = table.rows({ search: 'applied' }).count();
          $('#contadorResultados').text(totalFiltrado);
        });

        // Event listener para o botão limpar
        $('#limparBusca').on('click', function () {
          console.log('Limpando busca...');
          $('#buscarAluno').val('');
          $('#filtroTurma').val('').trigger('change');
          $('#filtroStatus').val('').trigger('change');
          buscarAlunos('');
          $('#buscarAluno').focus();
        });

        // Event listener para Enter no campo de busca
        $('#buscarAluno').on('keypress', function (e) {
          if (e.which === 13) { // Enter
            e.preventDefault();
            buscarAlunos($(this).val());
          }
        });

        // Atualizar contador quando a tabela for redimensionada
        table.on('draw', function () {
          var totalFiltrado = table.rows({ search: 'applied' }).count();
          $('#contadorResultados').text(totalFiltrado);
        });

        // Focar no campo de busca quando a página carregar
        setTimeout(function () {
          $('#buscarAluno').focus();
          console.log('Campo de busca focado');
        }, 500);

        console.log('Event listeners configurados');

      } catch (error) {
        console.error('Erro ao inicializar DataTable:', error);
        // Usar busca fallback
        buscaFallback();
      }
    });

    // Fallback: Busca JavaScript vanilla caso DataTable falhe
    function buscaFallback() {
      console.log('Usando busca fallback...');

      const campoBusca = document.getElementById('buscarAluno');
      const contador = document.getElementById('contadorResultados');
      const tabela = document.getElementById('tabelaAlunos');

      if (!campoBusca || !tabela) {
        console.error('Elementos não encontrados para busca fallback');
        return;
      }

      const tbody = tabela.getElementsByTagName('tbody')[0];
      if (!tbody) {
        console.error('Tbody não encontrado');
        return;
      }

      const linhas = tbody.getElementsByTagName('tr');

      campoBusca.addEventListener('keyup', function () {
        const termo = this.value.toLowerCase();
        let totalVisivel = 0;

        for (let i = 0; i < linhas.length; i++) {
          const linha = linhas[i];
          const texto = linha.textContent.toLowerCase();

          if (texto.includes(termo)) {
            linha.style.display = '';
            totalVisivel++;
          } else {
            linha.style.display = 'none';
          }
        }

        contador.textContent = totalVisivel;

        if (totalVisivel === 0 && termo.length > 0) {
          contador.innerHTML = '<span class="text-warning">Nenhum aluno encontrado para "' + termo + '"</span>';
        }
      });

      // Botão limpar
      const botaoLimpar = document.getElementById('limparBusca');
      if (botaoLimpar) {
        botaoLimpar.addEventListener('click', function () {
          campoBusca.value = '';
          campoBusca.dispatchEvent(new Event('keyup'));
          campoBusca.focus();
        });
      }

      console.log('Busca fallback configurada');
    }
  </script>
</body>

</html>