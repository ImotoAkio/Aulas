<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

echo "<h2>Atualização da Tabela Alunos</h2>";

try {
    // Verificar estrutura atual da tabela
    $stmt = $pdo->query("DESCRIBE alunos");
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
        'nome_completo' => 'VARCHAR(100)',
        'data_nascimento' => 'DATE',
        'sexo' => 'ENUM("M", "F", "Outro")',
        'cpf' => 'VARCHAR(14)',
        'rg' => 'VARCHAR(20)',
        'nis' => 'VARCHAR(20)',
        'nacionalidade' => 'VARCHAR(50)',
        'naturalidade_cidade' => 'VARCHAR(100)',
        'naturalidade_estado' => 'VARCHAR(50)',
        'endereco' => 'VARCHAR(255)',
        'numero' => 'VARCHAR(10)',
        'complemento' => 'VARCHAR(100)',
        'bairro' => 'VARCHAR(100)',
        'cep' => 'VARCHAR(10)',
        'cidade' => 'VARCHAR(100)',
        'estado' => 'VARCHAR(50)',
        'telefone' => 'VARCHAR(15)',
        'alergias' => 'TEXT',
        'nome_mae' => 'VARCHAR(100)',
        'cpf_mae' => 'VARCHAR(14)',
        'nome_pai' => 'VARCHAR(100)',
        'cpf_pai' => 'VARCHAR(14)',
        'telefone_responsavel' => 'VARCHAR(15)',
        'email_responsavel' => 'VARCHAR(100)',
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
        echo "<p style='color: green;'>✅ Todas as colunas já existem na tabela alunos!</p>";
    } else {
        echo "<h3>Colunas a serem adicionadas:</h3>";
        echo "<ul>";
        foreach ($colunas_para_adicionar as $coluna => $tipo) {
            echo "<li><strong>$coluna</strong> ($tipo)</li>";
        }
        echo "</ul>";

        // Adicionar colunas
        foreach ($colunas_para_adicionar as $coluna => $tipo) {
            try {
                $sql = "ALTER TABLE alunos ADD COLUMN $coluna $tipo";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Coluna <strong>$coluna</strong> adicionada com sucesso!</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Erro ao adicionar coluna <strong>$coluna</strong>: " . $e->getMessage() . "</p>";
            }
        }
    }

    // Atualizar nome para nome_completo se necessário
    if (in_array('nome', $colunas_existentes) && in_array('nome_completo', $colunas_existentes)) {
        try {
            $stmt = $pdo->query("UPDATE alunos SET nome_completo = nome WHERE nome_completo IS NULL OR nome_completo = ''");
            $linhas_afetadas = $stmt->rowCount();
            echo "<p style='color: blue;'>📝 <strong>$linhas_afetadas</strong> registros atualizados com nome_completo</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erro ao atualizar nome_completo: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>✅ Atualização concluída!</h3>";
    echo "<p><a href='listar_alunos.php' class='btn btn-primary'>Ir para Listagem de Alunos</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro geral: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Tabela Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Conteúdo PHP acima -->
    </div>
</body>
</html>
