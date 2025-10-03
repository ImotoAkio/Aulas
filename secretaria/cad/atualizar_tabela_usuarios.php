<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

echo "<h2>Atualização da Tabela Usuarios</h2>";

try {
    // Verificar estrutura atual da tabela
    $stmt = $pdo->query("DESCRIBE usuarios");
    $colunas_atuais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estrutura Atual:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas_atuais as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Lista de colunas que precisam ser adicionadas
    $colunas_necessarias = [
        'cpf' => 'VARCHAR(14)',
        'data_nascimento' => 'DATE',
        'sexo' => 'ENUM("M", "F", "Outro")',
        'rg' => 'VARCHAR(20)',
        'telefone' => 'VARCHAR(15)',
        'endereco' => 'VARCHAR(255)',
        'numero' => 'VARCHAR(10)',
        'complemento' => 'VARCHAR(100)',
        'bairro' => 'VARCHAR(100)',
        'cep' => 'VARCHAR(10)',
        'cidade' => 'VARCHAR(100)',
        'estado' => 'VARCHAR(50)',
        'formacao' => 'VARCHAR(100)',
        'area_formacao' => 'VARCHAR(100)',
        'observacoes' => 'TEXT'
    ];

    // Verificar quais colunas já existem
    $colunas_existentes = array_column($colunas_atuais, 'Field');
    $colunas_para_adicionar = [];

    foreach ($colunas_necessarias as $coluna => $tipo) {
        if (!in_array($coluna, $colunas_existentes)) {
            $colunas_para_adicionar[$coluna] = $tipo;
        }
    }

    if (empty($colunas_para_adicionar)) {
        echo "<p style='color: green;'>✓ Todas as colunas necessárias já existem na tabela usuarios!</p>";
    } else {
        echo "<h3>Colunas que serão adicionadas:</h3>";
        echo "<ul>";
        foreach ($colunas_para_adicionar as $coluna => $tipo) {
            echo "<li>$coluna ($tipo)</li>";
        }
        echo "</ul>";

        // Adicionar colunas
        foreach ($colunas_para_adicionar as $coluna => $tipo) {
            $sql = "ALTER TABLE usuarios ADD COLUMN $coluna $tipo";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ Coluna '$coluna' adicionada com sucesso!</p>";
        }

        echo "<p style='color: green;'>✓ Todas as colunas foram adicionadas com sucesso!</p>";
    }

    // Verificar estrutura final
    echo "<h3>Estrutura Final:</h3>";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $colunas_finais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas_finais as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p><a href='professor.php' class='btn btn-primary'>Ir para Cadastro de Professores</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Erro ao atualizar tabela: " . $e->getMessage() . "</p>";
    error_log("Erro ao atualizar tabela usuarios: " . $e->getMessage());
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h2, h3 {
    color: #333;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin: 20px 0;
    background: white;
}
th, td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}
th {
    background-color: #f8f9fa;
    font-weight: bold;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin: 10px 0;
}
.btn:hover {
    background-color: #0056b3;
}
</style>
