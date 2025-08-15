<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];

    // Criptografa a senha
    $senha_criptografada = password_hash($senha, PASSWORD_BCRYPT);

    // Insere o usuário no banco de dados
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (:nome, :email, :senha, :tipo)");
    $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'senha' => $senha_criptografada,
        'tipo' => $tipo
    ]);

    echo "<p style='color:green;'>Usuário cadastrado com sucesso!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Usuário</title>
</head>
<body>
    <h2>Cadastrar Usuário</h2>
    <form method="POST">
        <label>Nome:</label>
        <input type="text" name="nome" required><br>
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Senha:</label>
        <input type="password" name="senha" required><br>
        <label>Tipo de Usuário:</label>
        <select name="tipo" required>
            <option value="professor">Professor</option>
            <option value="coordenador">Coordenador</option>
        </select><br>
        <button type="submit">Cadastrar</button>
    </form>
</body>
</html>