<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cadastrar Aluno</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../../assets/vendors/select2/select2.min.css">
  <link rel="stylesheet" href="../../assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../../assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:../../partials/_navbar.html -->
    <?php include '../partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../../partials/_sidebar.html -->
      <?php include '../partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Cadastrar Aluno </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Cadastros</a></li>
                <li class="breadcrumb-item active" aria-current="page">Alunos</li>
              </ol>
            </nav>
          </div>
          <div class="row">


            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">

                  <form class="forms-sample">
                    <form method="POST" action="salvar_aluno.php" class="forms-sample">
                      <h5 class="mb-3">Dados do Aluno</h5>
                      <div class="form-group">
                        <label>Nome completo *</label>
                        <input type="text" name="nome_completo" class="form-control" required>
                      </div>
                      <div class="form-group">
                        <label>Data de nascimento *</label>
                        <input type="date" name="data_nascimento" class="form-control" required>
                      </div>
                      <div class="form-group">
                        <label>Sexo *</label>
                        <select name="sexo" class="form-select" required>
                          <option value="">Selecione</option>
                          <option value="M">Masculino</option>
                          <option value="F">Feminino</option>
                          <option value="Outro">Outro</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Nacionalidade</label>
                        <input type="text" name="nacionalidade" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Naturalidade (Cidade)</label>
                        <input type="text" name="naturalidade_cidade" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Naturalidade (Estado)</label>
                        <input type="text" name="naturalidade_estado" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>CPF *</label>
                        <input type="text" name="cpf" class="form-control" required maxlength="14">
                      </div>
                      <div class="form-group">
                        <label>RG</label>
                        <input type="text" name="rg" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Turma *</label>
                        <input type="number" name="turma_id" class="form-control" required>
                      </div>
                      <h5 class="mt-4 mb-3">Endereço</h5>
                      <div class="form-group">
                        <label>Rua</label>
                        <input type="text" name="endereco" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="numero" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="complemento" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="bairro" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="cep" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Estado</label>
                        <input type="text" name="estado" class="form-control">
                      </div>
                      <h5 class="mt-4 mb-3">Contato</h5>
                      <div class="form-group">
                        <label>Telefone 1</label>
                        <input type="text" name="telefone1" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Telefone 2</label>
                        <input type="text" name="telefone2" class="form-control">
                      </div>
                      <h5 class="mt-4 mb-3">Saúde</h5>
                      <div class="form-group">
                        <label>Tipo sanguíneo</label>
                        <input type="text" name="tipo_sanguineo" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Fator RH</label>
                        <input type="text" name="fator_rh" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Alergias</label>
                        <input type="text" name="alergias" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>NIS</label>
                        <input type="text" name="nis" class="form-control">
                      </div>
                      <h5 class="mt-4 mb-3">Responsáveis</h5>
                      <div class="form-group">
                        <label>Nome da mãe</label>
                        <input type="text" name="nome_mae" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>CPF da mãe</label>
                        <input type="text" name="cpf_mae" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>RG da mãe</label>
                        <input type="text" name="rg_mae" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Nome do pai</label>
                        <input type="text" name="nome_pai" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>CPF do pai</label>
                        <input type="text" name="cpf_pai" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>RG do pai</label>
                        <input type="text" name="rg_pai" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Nome do responsável legal</label>
                        <input type="text" name="nome_resp_legal" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>CPF do responsável legal</label>
                        <input type="text" name="cpf_resp_legal" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>RG do responsável legal</label>
                        <input type="text" name="rg_resp_legal" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Profissão do responsável legal</label>
                        <input type="text" name="profissao_resp_legal" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Local de trabalho do responsável legal</label>
                        <input type="text" name="local_trabalho_resp_legal" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Grau de parentesco do responsável legal</label>
                        <input type="text" name="grau_parentesco_resp_legal" class="form-control">
                      </div>
                      <button type="submit" class="btn btn-gradient-primary me-2">Cadastrar</button>
                      <button type="reset" class="btn btn-light">Limpar</button>
                    </form>
                  </form>
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
  <!-- plugins:js -->
  <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="../../assets/vendors/select2/select2.min.js"></script>
  <script src="../../assets/vendors/typeahead.js/typeahead.bundle.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../../assets/js/off-canvas.js"></script>
  <script src="../../assets/js/misc.js"></script>
  <script src="../../assets/js/settings.js"></script>
  <script src="../../assets/js/todolist.js"></script>
  <script src="../../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../../assets/js/file-upload.js"></script>
  <script src="../../assets/js/typeahead.js"></script>
  <script src="../../assets/js/select2.js"></script>
  <!-- End custom js for this page -->
</body>

</html>