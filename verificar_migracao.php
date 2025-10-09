<?php
/**
 * VERIFICADOR PÓS-MIGRAÇÃO
 * Verifica se a migração foi bem-sucedida
 */

require_once 'config/database.php';

echo "<h2>🔍 VERIFICAÇÃO PÓS-MIGRAÇÃO</h2>";

try {
    $pdo = getConnection();
    
    echo "<h3>📊 Verificando Estrutura do Banco</h3>";
    
    // Verificar tabelas
    $tabelas_necessarias = [
        'alunos', 'usuarios', 'turmas', 'pre_cadastros_controle', 
        'configuracoes_sistema', 'mensalidades'
    ];
    
    echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px;'>";
    echo "<h4>📋 Tabelas:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #e0e0e0;'>";
    echo "<th style='padding: 8px;'>Tabela</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Colunas</th>";
    echo "</tr>";
    
    foreach ($tabelas_necessarias as $tabela) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
            $existe = $stmt->fetch();
            
            if ($existe) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabela");
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->query("DESCRIBE $tabela");
                $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>$tabela</strong></td>";
                echo "<td style='padding: 8px; color: green;'>✅ Existe</td>";
                echo "<td style='padding: 8px;'>" . count($colunas) . " colunas</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>$tabela</strong></td>";
                echo "<td style='padding: 8px; color: red;'>❌ Não existe</td>";
                echo "<td style='padding: 8px;'>-</td>";
                echo "</tr>";
            }
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>$tabela</strong></td>";
            echo "<td style='padding: 8px; color: red;'>❌ Erro</td>";
            echo "<td style='padding: 8px;'>" . $e->getMessage() . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Verificar colunas específicas
    echo "<br><h3>📝 Verificando Colunas Importantes</h3>";
    
    $colunas_importantes = [
        'alunos' => ['nome_completo', 'telefone1', 'telefone2', 'status_cadastro', 'email'],
        'usuarios' => ['tipo'],
        'pre_cadastros_controle' => ['codigo_pre_cadastro', 'status', 'link_expiracao'],
        'configuracoes_sistema' => ['chave', 'valor']
    ];
    
    echo "<div style='background-color: #f0fff0; padding: 15px; border-radius: 5px;'>";
    echo "<h4>🔍 Colunas por Tabela:</h4>";
    
    foreach ($colunas_importantes as $tabela => $colunas) {
        echo "<h5>$tabela:</h5>";
        echo "<ul>";
        
        foreach ($colunas as $coluna) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM $tabela LIKE '$coluna'");
                $existe = $stmt->fetch();
                
                if ($existe) {
                    echo "<li style='color: green;'>✅ $coluna</li>";
                } else {
                    echo "<li style='color: red;'>❌ $coluna</li>";
                }
            } catch (Exception $e) {
                echo "<li style='color: red;'>❌ $coluna (Erro: " . $e->getMessage() . ")</li>";
            }
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // Verificar configurações
    echo "<br><h3>⚙️ Verificando Configurações</h3>";
    
    try {
        $stmt = $pdo->query("SELECT chave, valor FROM configuracoes_sistema");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($configs) {
            echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h4>🔧 Configurações Encontradas:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background-color: #e0e0e0;'>";
            echo "<th style='padding: 8px;'>Chave</th>";
            echo "<th style='padding: 8px;'>Valor</th>";
            echo "<th style='padding: 8px;'>Status</th>";
            echo "</tr>";
            
            foreach ($configs as $config) {
                $status = !empty($config['valor']) ? '✅ Configurado' : '⚠️ Vazio';
                $cor = !empty($config['valor']) ? 'green' : 'orange';
                
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>{$config['chave']}</strong></td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars(substr($config['valor'], 0, 50)) . "...</td>";
                echo "<td style='padding: 8px; color: $cor;'>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        } else {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "❌ <strong>Nenhuma configuração encontrada!</strong><br>";
            echo "Execute o script de migração novamente.";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>Erro ao verificar configurações:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    // Verificar dados de teste
    echo "<br><h3>📊 Verificando Dados</h3>";
    
    $verificacoes = [
        'alunos' => 'SELECT COUNT(*) as total FROM alunos',
        'usuarios' => 'SELECT COUNT(*) as total FROM usuarios',
        'turmas' => 'SELECT COUNT(*) as total FROM turmas',
        'pre_cadastros_controle' => 'SELECT COUNT(*) as total FROM pre_cadastros_controle'
    ];
    
    echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px;'>";
    echo "<h4>📈 Contagem de Registros:</h4>";
    echo "<ul>";
    
    foreach ($verificacoes as $tabela => $sql) {
        try {
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<li><strong>$tabela:</strong> {$result['total']} registros</li>";
        } catch (Exception $e) {
            echo "<li><strong>$tabela:</strong> ❌ Erro - " . $e->getMessage() . "</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
    
    // Resumo final
    echo "<br><hr>";
    echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px;'>";
    echo "<h3>✅ RESUMO DA VERIFICAÇÃO</h3>";
    echo "<p><strong>Se todas as verificações acima mostraram ✅ (verde), sua migração foi bem-sucedida!</strong></p>";
    echo "<p>Você pode prosseguir com o upload dos arquivos PHP atualizados.</p>";
    echo "</div>";
    
    echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ SE HOUVER PROBLEMAS:</h3>";
    echo "<ol>";
    echo "<li>Não continue com o upload dos arquivos</li>";
    echo "<li>Execute o script de migração novamente</li>";
    echo "<li>Se persistir, restaure o backup do banco</li>";
    echo "<li>Investigue o problema antes de prosseguir</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ ERRO CRÍTICO:</h3>";
    echo "<p><strong>Não foi possível conectar ao banco de dados!</strong></p>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique as configurações de conexão em <code>config/database.php</code></p>";
    echo "</div>";
}
?>
