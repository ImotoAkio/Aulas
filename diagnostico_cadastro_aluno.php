<?php
/**
 * DIAGNÓSTICO DE CADASTRO DE ALUNO
 * Identifica exatamente qual campo está causando erro
 */

echo "<h2>🔍 DIAGNÓSTICO DE CADASTRO DE ALUNO</h2>";

// Configurações do banco
$host = 'localhost';
$dbname = 'u894209272_app';
$user = 'u894209272_app';
$pass = 'Akio2604*';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Conectado ao banco</p>";
    
    // 1. Verificar se tabela alunos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'alunos'");
    $tabela_existe = $stmt->fetch();
    
    if (!$tabela_existe) {
        echo "<p style='color: red; font-size: 18px;'>❌ TABELA ALUNOS NÃO EXISTE!</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Tabela alunos existe</p>";
    
    // 2. Verificar estrutura da tabela alunos
    echo "<h3>📋 Estrutura da tabela alunos:</h3>";
    $stmt = $pdo->query("DESCRIBE alunos");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #e0e0e0;'>";
    echo "<th style='padding: 8px;'>Campo</th>";
    echo "<th style='padding: 8px;'>Tipo</th>";
    echo "<th style='padding: 8px;'>Null</th>";
    echo "<th style='padding: 8px;'>Key</th>";
    echo "<th style='padding: 8px;'>Default</th>";
    echo "</tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>{$coluna['Field']}</strong></td>";
        echo "<td style='padding: 8px;'>{$coluna['Type']}</td>";
        echo "<td style='padding: 8px;'>{$coluna['Null']}</td>";
        echo "<td style='padding: 8px;'>{$coluna['Key']}</td>";
        echo "<td style='padding: 8px;'>{$coluna['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Verificar campos que o código está tentando inserir
    echo "<h3>🔍 Campos que o código está tentando inserir:</h3>";
    
    $campos_codigo = [
        'nome', 'nome_completo', 'data_nascimento', 'sexo', 'nacionalidade',
        'naturalidade_cidade', 'naturalidade_estado', 'cpf', 'rg', 'turma_id', 'nis',
        'endereco', 'numero', 'complemento', 'bairro', 'cep', 'cidade', 'estado',
        'telefone1', 'telefone2', 'tipo_sanguineo', 'fator_rh', 'alergias',
        'nome_mae', 'cpf_mae', 'nome_pai', 'cpf_pai', 'nome_resp_legal', 'cpf_resp_legal',
        'profissao_resp_legal', 'grau_parentesco_resp_legal', 'local_trabalho_resp_legal'
    ];
    
    $campos_tabela = array_column($colunas, 'Field');
    
    echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px;'>";
    echo "<h4>Verificação de campos:</h4>";
    echo "<ul>";
    
    $campos_faltando = [];
    foreach ($campos_codigo as $campo) {
        if (in_array($campo, $campos_tabela)) {
            echo "<li style='color: green;'>✅ $campo</li>";
        } else {
            echo "<li style='color: red;'>❌ $campo (NÃO EXISTE)</li>";
            $campos_faltando[] = $campo;
        }
    }
    echo "</ul>";
    echo "</div>";
    
    // 4. Se houver campos faltando, mostrar como adicionar
    if (!empty($campos_faltando)) {
        echo "<h3>🔧 Campos que precisam ser adicionados:</h3>";
        echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<h4>SQL para adicionar campos faltando:</h4>";
        echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 3px;'>";
        
        foreach ($campos_faltando as $campo) {
            // Definir tipo baseado no nome do campo
            $tipo = 'VARCHAR(255) NULL';
            if (strpos($campo, 'cpf') !== false) $tipo = 'VARCHAR(14) NULL';
            if (strpos($campo, 'rg') !== false) $tipo = 'VARCHAR(20) NULL';
            if (strpos($campo, 'telefone') !== false) $tipo = 'VARCHAR(20) NULL';
            if (strpos($campo, 'cep') !== false) $tipo = 'VARCHAR(10) NULL';
            if (strpos($campo, 'nis') !== false) $tipo = 'VARCHAR(20) NULL';
            if (strpos($campo, 'data') !== false) $tipo = 'DATE NULL';
            if (strpos($campo, 'tipo_sanguineo') !== false) $tipo = 'VARCHAR(5) NULL';
            if (strpos($campo, 'fator_rh') !== false) $tipo = 'VARCHAR(3) NULL';
            if (strpos($campo, 'sexo') !== false) $tipo = "ENUM('M', 'F') NULL";
            if (strpos($campo, 'turma_id') !== false) $tipo = 'BIGINT UNSIGNED NULL';
            if (strpos($campo, 'alergias') !== false) $tipo = 'TEXT NULL';
            
            echo "ALTER TABLE alunos ADD COLUMN $campo $tipo;\n";
        }
        echo "</pre>";
        echo "</div>";
        
        // 5. Executar automaticamente a correção
        echo "<h3>🔧 Executando correção automática:</h3>";
        
        foreach ($campos_faltando as $campo) {
            $tipo = 'VARCHAR(255) NULL';
            if (strpos($campo, 'cpf') !== false) $tipo = 'VARCHAR(14) NULL';
            if (strpos($campo, 'rg') !== false) $tipo = 'VARCHAR(20) NULL';
            if (strpos($campo, 'telefone') !== false) $tipo = 'VARCHAR(20) NULL';
            if (strpos($campo, 'cep') !== false) $tipo = 'VARCHAR(10) NULL';
            if (strpos($campo, 'nis') !== false) $tipo = 'VARCHAR(20) NULL';
            if (strpos($campo, 'data') !== false) $tipo = 'DATE NULL';
            if (strpos($campo, 'tipo_sanguineo') !== false) $tipo = 'VARCHAR(5) NULL';
            if (strpos($campo, 'fator_rh') !== false) $tipo = 'VARCHAR(3) NULL';
            if (strpos($campo, 'sexo') !== false) $tipo = "ENUM('M', 'F') NULL";
            if (strpos($campo, 'turma_id') !== false) $tipo = 'BIGINT UNSIGNED NULL';
            if (strpos($campo, 'alergias') !== false) $tipo = 'TEXT NULL';
            
            try {
                $sql = "ALTER TABLE alunos ADD COLUMN $campo $tipo";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Campo $campo adicionado</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Erro ao adicionar $campo: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // 6. Teste de inserção
    echo "<h3>🧪 Teste de inserção:</h3>";
    
    try {
        $sql = "INSERT INTO alunos (
            nome, nome_completo, data_nascimento, sexo, nacionalidade, 
            naturalidade_cidade, naturalidade_estado, cpf, rg, turma_id, nis,
            endereco, numero, complemento, bairro, cep, cidade, estado,
            telefone1, telefone2, tipo_sanguineo, fator_rh, alergias,
            nome_mae, cpf_mae, nome_pai, cpf_pai, nome_resp_legal, cpf_resp_legal,
            profissao_resp_legal, grau_parentesco_resp_legal, local_trabalho_resp_legal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            'Teste', // nome
            'Teste Completo', // nome_completo
            '2020-01-01', // data_nascimento
            'M', // sexo
            'Brasileira', // nacionalidade
            'Petrolina', // naturalidade_cidade
            'PE', // naturalidade_estado
            '12345678901', // cpf
            '123456789', // rg
            1, // turma_id
            '123456789', // nis
            'Rua Teste', // endereco
            '123', // numero
            'Apto 1', // complemento
            'Centro', // bairro
            '56300000', // cep
            'Petrolina', // cidade
            'PE', // estado
            '87999999999', // telefone1
            '87988888888', // telefone2
            'A+', // tipo_sanguineo
            '+', // fator_rh
            'Nenhuma', // alergias
            'Mãe Teste', // nome_mae
            '11111111111', // cpf_mae
            'Pai Teste', // nome_pai
            '22222222222', // cpf_pai
            'Responsável Teste', // nome_resp_legal
            '33333333333', // cpf_resp_legal
            'Profissão Teste', // profissao_resp_legal
            'Pai', // grau_parentesco_resp_legal
            'Empresa Teste' // local_trabalho_resp_legal
        ]);
        
        if ($resultado) {
            echo "<p style='color: green; font-size: 18px;'>✅ <strong>TESTE DE INSERÇÃO FUNCIONOU!</strong></p>";
            echo "<p>ID inserido: " . $pdo->lastInsertId() . "</p>";
            
            // Limpar teste
            $pdo->exec("DELETE FROM alunos WHERE nome = 'Teste'");
            echo "<p style='color: green;'>✅ Dados de teste removidos</p>";
            
            echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px; margin-top: 20px;'>";
            echo "<h4>🎉 PROBLEMA RESOLVIDO!</h4>";
            echo "<p>A tabela alunos está funcionando corretamente.</p>";
            echo "<p>Agora você pode cadastrar alunos normalmente.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro no teste: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
}
?>
