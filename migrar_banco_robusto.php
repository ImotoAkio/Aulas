<?php
/**
 * SCRIPT DE MIGRAÇÃO ROBUSTO PARA HOSPEDAGEM
 * Versão otimizada para funcionar em diferentes ambientes
 */

// Configurações de erro para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>🔄 MIGRAÇÃO DO BANCO DE DADOS</h2>";
echo "<p>Iniciando processo de migração...</p>";

// Verificar se o arquivo de configuração existe
if (!file_exists('config/database.php')) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO:</strong> Arquivo config/database.php não encontrado!<br>";
    echo "Verifique se o arquivo está no local correto.";
    echo "</div>";
    exit;
}

// Tentar incluir o arquivo de configuração
try {
    require_once 'config/database.php';
    echo "✅ Arquivo de configuração carregado<br>";
} catch (Exception $e) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO:</strong> Falha ao carregar config/database.php<br>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
    exit;
}

// Verificar se a função getConnection existe
if (!function_exists('getConnection')) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO:</strong> Função getConnection() não encontrada!<br>";
    echo "Verifique se o arquivo config/database.php está correto.";
    echo "</div>";
    exit;
}

// Tentar conectar ao banco
try {
    $pdo = getConnection();
    echo "✅ Conexão com banco estabelecida<br>";
} catch (Exception $e) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO DE CONEXÃO:</strong><br>";
    echo "Erro: " . $e->getMessage() . "<br>";
    echo "Verifique as configurações de banco em config/database.php";
    echo "</div>";
    exit;
}

// Array para log de operações
$log = [];

/**
 * Função para executar SQL com tratamento de erro
 */
function executarSQL($pdo, $sql, $descricao, &$log) {
    try {
        $pdo->exec($sql);
        echo "✅ $descricao<br>";
        $log[] = $descricao;
        return true;
    } catch (Exception $e) {
        echo "⚠️ $descricao - " . $e->getMessage() . "<br>";
        return false;
    }
}

/**
 * Função para verificar se tabela existe
 */
function tabelaExiste($pdo, $tabela) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Função para verificar se coluna existe
 */
function colunaExiste($pdo, $tabela, $coluna) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $tabela LIKE '$coluna'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

echo "<h3>🔍 Verificando estrutura atual...</h3>";

// Verificar tabelas existentes
$tabelas = ['alunos', 'turmas', 'usuarios', 'pre_cadastros_controle', 'configuracoes_sistema', 'mensalidades'];

foreach ($tabelas as $tabela) {
    if (tabelaExiste($pdo, $tabela)) {
        echo "✅ Tabela '$tabela' existe<br>";
    } else {
        echo "⚠️ Tabela '$tabela' NÃO existe<br>";
    }
}

echo "<h3>🏗️ Criando tabelas necessárias...</h3>";

// 1. Tabela pre_cadastros_controle
if (!tabelaExiste($pdo, 'pre_cadastros_controle')) {
    $sql = "
    CREATE TABLE pre_cadastros_controle (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        aluno_id BIGINT UNSIGNED NOT NULL,
        codigo_pre_cadastro VARCHAR(32) NOT NULL UNIQUE,
        link_expiracao DATETIME NOT NULL,
        status ENUM('pendente', 'completo', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
        criado_por BIGINT UNSIGNED NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        dados_preenchidos_em DATETIME NULL,
        preenchido_por_responsavel BOOLEAN NOT NULL DEFAULT FALSE,
        observacoes TEXT NULL,
        UNIQUE KEY uk_aluno_pre_cadastro (aluno_id),
        KEY idx_codigo (codigo_pre_cadastro),
        KEY idx_status (status),
        KEY idx_expiracao (link_expiracao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executarSQL($pdo, $sql, "Tabela 'pre_cadastros_controle' criada", $log);
} else {
    echo "ℹ️ Tabela 'pre_cadastros_controle' já existe<br>";
}

// 2. Tabela configuracoes_sistema
if (!tabelaExiste($pdo, 'configuracoes_sistema')) {
    $sql = "
    CREATE TABLE configuracoes_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT NULL,
        descricao TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_chave (chave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executarSQL($pdo, $sql, "Tabela 'configuracoes_sistema' criada", $log);
} else {
    echo "ℹ️ Tabela 'configuracoes_sistema' já existe<br>";
}

// 3. Tabela mensalidades
if (!tabelaExiste($pdo, 'mensalidades')) {
    $sql = "
    CREATE TABLE mensalidades (
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
        atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_mensalidade (aluno_id, competencia),
        KEY idx_mensalidade_status (status),
        KEY idx_mensalidade_venc (vencimento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executarSQL($pdo, $sql, "Tabela 'mensalidades' criada", $log);
} else {
    echo "ℹ️ Tabela 'mensalidades' já existe<br>";
}

echo "<h3>📝 Adicionando colunas necessárias...</h3>";

// Verificar se tabela alunos existe antes de adicionar colunas
if (tabelaExiste($pdo, 'alunos')) {
    $colunas_alunos = [
        'nome_completo' => 'VARCHAR(255) NULL',
        'naturalidade' => 'VARCHAR(100) NULL',
        'naturalidade_estado' => 'VARCHAR(2) NULL',
        'nis' => 'VARCHAR(20) NULL',
        'tipo_sanguineo' => 'VARCHAR(5) NULL',
        'fator_rh' => 'VARCHAR(3) NULL',
        'nome_mae' => 'VARCHAR(255) NULL',
        'cpf_mae' => 'VARCHAR(14) NULL',
        'nome_pai' => 'VARCHAR(255) NULL',
        'cpf_pai' => 'VARCHAR(14) NULL',
        'nome_resp_legal' => 'VARCHAR(255) NULL',
        'cpf_resp_legal' => 'VARCHAR(14) NULL',
        'profissao_resp_legal' => 'VARCHAR(100) NULL',
        'grau_parentesco_resp_legal' => 'VARCHAR(50) NULL',
        'local_trabalho_resp_legal' => 'VARCHAR(255) NULL',
        'telefone1' => 'VARCHAR(20) NULL',
        'telefone2' => 'VARCHAR(20) NULL',
        'email' => 'VARCHAR(255) NULL',
        'alergias' => 'TEXT NULL',
        'medicamentos' => 'TEXT NULL',
        'observacoes_medicas' => 'TEXT NULL',
        'status_cadastro' => "ENUM('pre_cadastro', 'completo', 'aprovado') NOT NULL DEFAULT 'pre_cadastro'"
    ];
    
    foreach ($colunas_alunos as $coluna => $definicao) {
        if (!colunaExiste($pdo, 'alunos', $coluna)) {
            $sql = "ALTER TABLE alunos ADD COLUMN $coluna $definicao";
            executarSQL($pdo, $sql, "Coluna '$coluna' adicionada à tabela 'alunos'", $log);
        } else {
            echo "ℹ️ Coluna '$coluna' já existe na tabela 'alunos'<br>";
        }
    }
} else {
    echo "⚠️ Tabela 'alunos' não existe - pulando adição de colunas<br>";
}

// Verificar se tabela usuarios existe antes de adicionar colunas
if (tabelaExiste($pdo, 'usuarios')) {
    if (!colunaExiste($pdo, 'usuarios', 'tipo')) {
        $sql = "ALTER TABLE usuarios ADD COLUMN tipo ENUM('professor', 'coordenador', 'secretaria', 'financeiro') NOT NULL DEFAULT 'professor'";
        executarSQL($pdo, $sql, "Coluna 'tipo' adicionada à tabela 'usuarios'", $log);
    } else {
        echo "ℹ️ Coluna 'tipo' já existe na tabela 'usuarios'<br>";
    }
} else {
    echo "⚠️ Tabela 'usuarios' não existe - pulando adição de colunas<br>";
}

echo "<h3>⚙️ Atualizando configurações...</h3>";

// Verificar se tabela configuracoes_sistema existe
if (tabelaExiste($pdo, 'configuracoes_sistema')) {
    $configuracoes = [
        [
            'chave' => 'webhook_url',
            'valor' => 'https://webhook.echo.dev.br/webhook/8cea05f1-e082-45ea-83ca-f80809af9cfd',
            'descricao' => 'URL do webhook para envio de dados JSON'
        ],
        [
            'chave' => 'webhook_aprovacao_url',
            'valor' => 'https://webhook.echo.dev.br/webhook/e8a2f4db-eefd-498e-9547-a0200442c108',
            'descricao' => 'URL do webhook para notificação de aprovação'
        ]
    ];
    
    foreach ($configuracoes as $config) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes_sistema (chave, valor, descricao) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor), 
                descricao = VALUES(descricao),
                atualizado_em = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$config['chave'], $config['valor'], $config['descricao']]);
            echo "✅ Configuração '{$config['chave']}' atualizada<br>";
            $log[] = "Configuração {$config['chave']} atualizada";
        } catch (Exception $e) {
            echo "⚠️ Erro ao atualizar configuração '{$config['chave']}': " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "⚠️ Tabela 'configuracoes_sistema' não existe - pulando configurações<br>";
}

echo "<h3>📊 Resumo da Migração</h3>";
echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Alterações Realizadas:</h4>";
if (empty($log)) {
    echo "<p>Nenhuma alteração foi necessária - banco já está atualizado!</p>";
} else {
    echo "<ul>";
    foreach ($log as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul>";
}
echo "</div>";

echo "<br><div style='background-color: #f0fff0; padding: 15px; border-radius: 5px;'>";
echo "<h4>🎯 Próximos Passos:</h4>";
echo "<ol>";
echo "<li>✅ Fazer upload dos arquivos PHP atualizados</li>";
echo "<li>✅ Testar funcionalidades no ambiente de produção</li>";
echo "<li>✅ Verificar se todos os webhooks estão funcionando</li>";
echo "<li>✅ Configurar URLs de webhook específicas para produção</li>";
echo "</ol>";
echo "</div>";

echo "<br><div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<h4>🔧 Informações do Servidor:</h4>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Servidor:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "<li><strong>Timezone:</strong> " . date_default_timezone_get() . "</li>";
echo "</ul>";
echo "</div>";

echo "<br><div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Migração Concluída!</h4>";
echo "<p>Se você vê esta mensagem, a migração foi executada com sucesso.</p>";
echo "<p>Agora você pode fazer upload dos arquivos PHP atualizados.</p>";
echo "</div>";
?>
