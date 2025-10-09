<?php
/**
 * MIGRAÇÃO SIMPLES - COMPATIBILIDADE MÁXIMA
 * Versão sem classes para hospedagens com restrições
 */

// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Migração Banco</title></head><body>";
echo "<h2>🔄 MIGRAÇÃO SIMPLES DO BANCO</h2>";

// Verificar arquivo de configuração
if (!file_exists('config/database.php')) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "❌ ERRO: Arquivo config/database.php não encontrado!";
    echo "</div>";
    exit;
}

// Carregar configuração
require_once 'config/database.php';

// Verificar função de conexão
if (!function_exists('getConnection')) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "❌ ERRO: Função getConnection() não encontrada!";
    echo "</div>";
    exit;
}

// Conectar ao banco
try {
    $pdo = getConnection();
    echo "<p style='color: green;'>✅ Conectado ao banco de dados</p>";
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "❌ ERRO DE CONEXÃO: " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h3>🔍 Verificando tabelas...</h3>";

// Verificar tabelas existentes
$tabelas_existentes = [];
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ Encontradas " . count($tabelas_existentes) . " tabelas</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao listar tabelas: " . $e->getMessage() . "</p>";
}

echo "<h3>🏗️ Criando tabelas necessárias...</h3>";

// 1. Tabela pre_cadastros_controle
if (!in_array('pre_cadastros_controle', $tabelas_existentes)) {
    $sql = "CREATE TABLE pre_cadastros_controle (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        aluno_id BIGINT UNSIGNED NOT NULL,
        codigo_pre_cadastro VARCHAR(32) NOT NULL UNIQUE,
        link_expiracao DATETIME NOT NULL,
        status ENUM('pendente', 'completo', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
        criado_por BIGINT UNSIGNED NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        dados_preenchidos_em DATETIME NULL,
        preenchido_por_responsavel BOOLEAN NOT NULL DEFAULT FALSE,
        observacoes TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabela pre_cadastros_controle criada</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao criar pre_cadastros_controle: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Tabela pre_cadastros_controle já existe</p>";
}

// 2. Tabela configuracoes_sistema
if (!in_array('configuracoes_sistema', $tabelas_existentes)) {
    $sql = "CREATE TABLE configuracoes_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT NULL,
        descricao TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabela configuracoes_sistema criada</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao criar configuracoes_sistema: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Tabela configuracoes_sistema já existe</p>";
}

// 3. Tabela mensalidades
if (!in_array('mensalidades', $tabelas_existentes)) {
    $sql = "CREATE TABLE mensalidades (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        aluno_id BIGINT UNSIGNED NOT NULL,
        competencia CHAR(7) NOT NULL,
        valor_original DECIMAL(10,2) NOT NULL,
        desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
        acrescimos DECIMAL(10,2) NOT NULL DEFAULT 0,
        valor_final DECIMAL(10,2) NOT NULL,
        vencimento DATE NOT NULL,
        status ENUM('gerada','enviada','paga','pendente','atrasada','cancelada') NOT NULL DEFAULT 'gerada',
        boleto_nosso_numero VARCHAR(50) NULL,
        pix_txid VARCHAR(70) NULL,
        gateway_charge_id VARCHAR(80) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabela mensalidades criada</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao criar mensalidades: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Tabela mensalidades já existe</p>";
}

echo "<h3>📝 Adicionando colunas...</h3>";

// Verificar se tabela alunos existe
if (in_array('alunos', $tabelas_existentes)) {
    // Lista de colunas para adicionar
    $colunas = [
        'nome_completo' => 'VARCHAR(255) NULL',
        'telefone1' => 'VARCHAR(20) NULL',
        'telefone2' => 'VARCHAR(20) NULL',
        'email' => 'VARCHAR(255) NULL',
        'status_cadastro' => "ENUM('pre_cadastro', 'completo', 'aprovado') NOT NULL DEFAULT 'pre_cadastro'"
    ];
    
    foreach ($colunas as $coluna => $definicao) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM alunos LIKE '$coluna'");
            $existe = $stmt->fetch();
            
            if (!$existe) {
                $sql = "ALTER TABLE alunos ADD COLUMN $coluna $definicao";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Coluna $coluna adicionada</p>";
            } else {
                echo "<p>ℹ️ Coluna $coluna já existe</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Erro ao adicionar coluna $coluna: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela alunos não encontrada</p>";
}

// Verificar se tabela usuarios existe
if (in_array('usuarios', $tabelas_existentes)) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'tipo'");
        $existe = $stmt->fetch();
        
        if (!$existe) {
            $sql = "ALTER TABLE usuarios ADD COLUMN tipo ENUM('professor', 'coordenador', 'secretaria', 'financeiro') NOT NULL DEFAULT 'professor'";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Coluna tipo adicionada à tabela usuarios</p>";
        } else {
            echo "<p>ℹ️ Coluna tipo já existe na tabela usuarios</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao adicionar coluna tipo: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela usuarios não encontrada</p>";
}

echo "<h3>⚙️ Configurando sistema...</h3>";

// Inserir configurações
if (in_array('configuracoes_sistema', $tabelas_existentes)) {
    $configs = [
        ['webhook_url', 'https://webhook.echo.dev.br/webhook/8cea05f1-e082-45ea-83ca-f80809af9cfd', 'URL do webhook para envio de dados JSON'],
        ['webhook_aprovacao_url', 'https://webhook.echo.dev.br/webhook/e8a2f4db-eefd-498e-9547-a0200442c108', 'URL do webhook para notificação de aprovação']
    ];
    
    foreach ($configs as $config) {
        try {
            $stmt = $pdo->prepare("INSERT INTO configuracoes_sistema (chave, valor, descricao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $stmt->execute($config);
            echo "<p style='color: green;'>✅ Configuração {$config[0]} inserida</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Erro ao inserir configuração {$config[0]}: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela configuracoes_sistema não encontrada</p>";
}

echo "<h3>✅ Migração Concluída!</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Fazer upload dos arquivos PHP atualizados</li>";
echo "<li>Testar as funcionalidades</li>";
echo "<li>Configurar URLs de webhook específicas</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>
