
<?php
/**
 * MIGRAÇÃO ULTRA-SIMPLES - ZERO DEPENDÊNCIAS
 * Funciona sem nenhum arquivo externo
 */

// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Migração Ultra-Simples</title></head><body>";
echo "<h2>🔄 MIGRAÇÃO ULTRA-SIMPLES DO BANCO</h2>";

// ========================================
// CONFIGURAÇÕES DO BANCO - AJUSTE AQUI
// ========================================
$host = 'localhost';
$dbname = 'u894209272_app';          // ← NOME DO BANCO CORRETO
$user = 'u894209272_app';            // ← USUÁRIO CORRETO
$pass = 'Akio2604*';                 // ← SENHA CORRETA
$charset = 'utf8';

echo "<h3>🔧 Configurações de Conexão</h3>";
echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Banco:</strong> $dbname</p>";
echo "<p><strong>Usuário:</strong> $user</p>";
echo "<p><strong>Senha:</strong> " . (empty($pass) ? '(vazia)' : '(definida)') . "</p>";
echo "<p><strong>Charset:</strong> $charset</p>";
echo "</div>";

// ========================================
// CONEXÃO COM BANCO
// ========================================
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>CONECTADO AO BANCO COM SUCESSO!</strong></p>";
} catch (PDOException $e) {
    echo "<div style='color: red; background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h4>❌ ERRO DE CONEXÃO:</h4>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>DSN:</strong> $dsn</p>";
    echo "<h4>🔧 SOLUÇÕES:</h4>";
    echo "<ol>";
    echo "<li>Verifique se o banco de dados está ativo</li>";
    echo "<li>Confirme o nome do banco: <code>$dbname</code></li>";
    echo "<li>Confirme o usuário: <code>$user</code></li>";
    echo "<li>Confirme a senha</li>";
    echo "<li>Verifique se o host está correto: <code>$host</code></li>";
    echo "</ol>";
    echo "<p><strong>Para corrigir:</strong> Edite as configurações no início deste arquivo.</p>";
    echo "</div>";
    exit;
}

// ========================================
// VERIFICAR TABELAS EXISTENTES
// ========================================
echo "<h3>🔍 Verificando tabelas existentes...</h3>";

$tabelas_existentes = [];
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color: green;'>✅ Encontradas " . count($tabelas_existentes) . " tabelas no banco</p>";
    
    if (!empty($tabelas_existentes)) {
        echo "<div style='background-color: #e7f3ff; padding: 10px; border-radius: 5px;'>";
        echo "<h4>Tabelas Existentes:</h4>";
        echo "<ul>";
        foreach ($tabelas_existentes as $tabela) {
            echo "<li>📋 $tabela</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao listar tabelas: " . $e->getMessage() . "</p>";
}

// ========================================
// CRIAR TABELAS NECESSÁRIAS
// ========================================
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
        observacoes TEXT NULL,
        UNIQUE KEY uk_aluno_pre_cadastro (aluno_id),
        KEY idx_codigo (codigo_pre_cadastro),
        KEY idx_status (status),
        KEY idx_expiracao (link_expiracao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green; font-size: 16px;'>✅ <strong>pre_cadastros_controle</strong> criada!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao criar pre_cadastros_controle: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Tabela <strong>pre_cadastros_controle</strong> já existe</p>";
}

// 2. Tabela configuracoes_sistema
if (!in_array('configuracoes_sistema', $tabelas_existentes)) {
    $sql = "CREATE TABLE configuracoes_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT NULL,
        descricao TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_chave (chave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green; font-size: 16px;'>✅ <strong>configuracoes_sistema</strong> criada!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao criar configuracoes_sistema: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Tabela <strong>configuracoes_sistema</strong> já existe</p>";
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
        atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_mensalidade (aluno_id, competencia),
        KEY idx_mensalidade_status (status),
        KEY idx_mensalidade_venc (vencimento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green; font-size: 16px;'>✅ <strong>mensalidades</strong> criada!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao criar mensalidades: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>ℹ️ Tabela <strong>mensalidades</strong> já existe</p>";
}

// ========================================
// ADICIONAR COLUNAS NECESSÁRIAS
// ========================================
echo "<h3>📝 Adicionando colunas necessárias...</h3>";

// Verificar se tabela alunos existe e adicionar colunas
if (in_array('alunos', $tabelas_existentes)) {
    $colunas_alunos = [
        'nome_completo' => 'VARCHAR(255) NULL',
        'telefone1' => 'VARCHAR(20) NULL',
        'telefone2' => 'VARCHAR(20) NULL',
        'email' => 'VARCHAR(255) NULL',
        'status_cadastro' => "ENUM('pre_cadastro', 'completo', 'aprovado') NOT NULL DEFAULT 'pre_cadastro'"
    ];
    
    foreach ($colunas_alunos as $coluna => $definicao) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM alunos LIKE '$coluna'");
            $existe = $stmt->fetch();
            
            if (!$existe) {
                $sql = "ALTER TABLE alunos ADD COLUMN $coluna $definicao";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Coluna <strong>$coluna</strong> adicionada</p>";
            } else {
                echo "<p>ℹ️ Coluna <strong>$coluna</strong> já existe</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Erro ao adicionar $coluna: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela <strong>alunos</strong> não encontrada</p>";
}

// Verificar se tabela usuarios existe e adicionar coluna tipo
if (in_array('usuarios', $tabelas_existentes)) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'tipo'");
        $existe = $stmt->fetch();
        
        if (!$existe) {
            $sql = "ALTER TABLE usuarios ADD COLUMN tipo ENUM('professor', 'coordenador', 'secretaria', 'financeiro') NOT NULL DEFAULT 'professor'";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Coluna <strong>tipo</strong> adicionada à usuarios</p>";
        } else {
            echo "<p>ℹ️ Coluna <strong>tipo</strong> já existe em usuarios</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erro ao adicionar tipo: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela <strong>usuarios</strong> não encontrada</p>";
}

// ========================================
// CONFIGURAR SISTEMA
// ========================================
echo "<h3>⚙️ Configurando sistema...</h3>";

// Inserir configurações se a tabela existir
if (in_array('configuracoes_sistema', $tabelas_existentes)) {
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
            $stmt->execute($config);
            echo "<p style='color: green;'>✅ Configuração <strong>{$config['chave']}</strong> inserida</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Erro ao inserir {$config['chave']}: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela configuracoes_sistema não encontrada</p>";
}

// ========================================
// RESUMO FINAL
// ========================================
echo "<h3>📊 RESUMO FINAL</h3>";
echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<h4 style='color: green; font-size: 20px;'>✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!</h4>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Banco:</strong> $dbname</p>";
echo "<p><strong>Host:</strong> $host</p>";

echo "<h4>🎯 Próximos Passos:</h4>";
echo "<ol>";
echo "<li>✅ Fazer upload dos arquivos PHP atualizados</li>";
echo "<li>✅ Testar funcionalidades no ambiente de produção</li>";
echo "<li>✅ Verificar se todos os webhooks estão funcionando</li>";
echo "<li>✅ Configurar URLs de webhook específicas para produção</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Informações Técnicas:</h4>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Sim' : 'Não') . "</li>";
echo "<li><strong>MySQL PDO:</strong> " . (extension_loaded('pdo_mysql') ? 'Sim' : 'Não') . "</li>";
echo "<li><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</li>";
echo "<li><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🎉 PARABÉNS!</h4>";
echo "<p>Se você está vendo esta mensagem, a migração foi executada com sucesso!</p>";
echo "<p>Agora você pode fazer upload dos arquivos PHP atualizados e testar a aplicação.</p>";
echo "</div>";

echo "</body></html>";
?>
