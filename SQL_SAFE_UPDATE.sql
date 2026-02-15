-- ============================================================
-- SQL_SAFE_UPDATE.sql
-- ============================================================
-- Este script pode ser rodado múltiplas vezes sem causar erros.
-- Ele verifica se colunas/tabelas existem antes de tentar criar.

DELIMITER //

-- ------------------------------------------------------------
-- PROCEDURE: Adicionar Coluna se não existir
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS AddColumnIfNotExists //
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(255),
    IN colName VARCHAR(255),
    IN colDef TEXT
)
BEGIN
    DECLARE colCount INT;
    
    SELECT COUNT(*) INTO colCount
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = tableName
    AND column_name = colName;
    
    IF colCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', colName, ' ', colDef);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('✅ Coluna ', colName, ' adicionada em ', tableName) AS status;
    ELSE
        SELECT CONCAT('ℹ️  Coluna ', colName, ' ja existe em ', tableName) AS status;
    END IF;
END //

-- ------------------------------------------------------------
-- PROCEDURE: Remover Index se existir
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS DropIndexIfExists //
CREATE PROCEDURE DropIndexIfExists(
    IN tableName VARCHAR(255),
    IN indexName VARCHAR(255)
)
BEGIN
    DECLARE idxCount INT;
    
    SELECT COUNT(*) INTO idxCount
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = tableName
    AND index_name = indexName;
    
    IF idxCount > 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' DROP INDEX ', indexName);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('✅ Index ', indexName, ' removido de ', tableName) AS status;
    ELSE
        SELECT CONCAT('ℹ️  Index ', indexName, ' nao encontrado em ', tableName) AS status;
    END IF;
END //

-- ------------------------------------------------------------
-- PROCEDURE: Criar Tabelas Financeiras
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS CreateRecibosTables //
CREATE PROCEDURE CreateRecibosTables()
BEGIN
    -- Criar tabela 'recibos' se não existir
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'recibos') THEN
        CREATE TABLE recibos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            tipo ENUM('mensalidade', 'fardamento', 'atividade', 'matricula') NOT NULL,
            referencia VARCHAR(100) NULL,
            valor_original DECIMAL(10,2) NOT NULL,
            desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
            acrescimos DECIMAL(10,2) NOT NULL DEFAULT 0,
            valor_final DECIMAL(10,2) NOT NULL,
            vencimento DATE NULL,
            data_pagamento DATE NULL,
            status ENUM('gerado', 'emitido', 'pago', 'cancelado') NOT NULL DEFAULT 'gerado',
            descricao TEXT NULL,
            observacoes TEXT NULL,
            forma_pagamento VARCHAR(50) NULL,
            numero_recibo VARCHAR(50) NULL,
            numero_nf VARCHAR(50) NULL,
            gerado_por BIGINT UNSIGNED NULL,
            pago_por BIGINT UNSIGNED NULL,
            cancelado_por BIGINT UNSIGNED NULL,
            motivo_cancelamento TEXT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            cancelado_em DATETIME NULL,
            KEY idx_tipo (tipo),
            KEY idx_status (status),
            KEY idx_aluno (aluno_id),
            KEY idx_vencimento (vencimento),
            KEY idx_data_pagamento (data_pagamento),
            KEY idx_numero_recibo (numero_recibo),
            KEY idx_tipo_status (tipo, status),
            CONSTRAINT fk_recibo_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SELECT '✅ Tabela recibos criada' AS status;
    ELSE
        SELECT 'ℹ️  Tabela recibos ja existe' AS status;
    END IF;

    -- Criar tabela 'recibo_itens' se não existir
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'recibo_itens') THEN
        CREATE TABLE recibo_itens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recibo_id BIGINT UNSIGNED NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            quantidade DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            unidade VARCHAR(20) NULL,
            valor_unitario DECIMAL(10,2) NOT NULL,
            valor_total DECIMAL(10,2) NOT NULL,
            ordem INT NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_recibo (recibo_id),
            KEY idx_ordem (recibo_id, ordem),
            CONSTRAINT fk_recibo_itens_recibo FOREIGN KEY (recibo_id) REFERENCES recibos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SELECT '✅ Tabela recibo_itens criada' AS status;
    ELSE
        SELECT 'ℹ️  Tabela recibo_itens ja existe' AS status;
    END IF;
END //

DELIMITER ;

-- ============================================================
-- EXECUTANDO AS MUDANÇAS
-- ============================================================

-- 1. Adicionar turma_futura_id (com index)
CALL AddColumnIfNotExists('pre_cadastros_controle', 'turma_futura_id', 'BIGINT UNSIGNED NULL AFTER aluno_id');

-- Tentativa de criar indice se nao existir (Feature do MariaDB 10.1+ / MySQL 5.7+ para CREATE INDEX IF NOT EXISTS)
-- Se der erro de sintaxe em versoes muito antigas, pode ser ignorado pois o script continua.
-- Mas vamos usar um SELECT COUNT para ser safe.

SET @index_exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_name = 'pre_cadastros_controle' 
    AND index_name = 'idx_turma_futura' 
    AND table_schema = DATABASE()
);

SET @sql_stmt := IF(
    @index_exists = 0, 
    'ALTER TABLE pre_cadastros_controle ADD INDEX idx_turma_futura (turma_futura_id)', 
    'SELECT "Index idx_turma_futura ja existe" as status'
);

PREPARE stmt FROM @sql_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2. Remover constraint CPF duplicada
CALL DropIndexIfExists('alunos', 'uk_alunos_cpf');

-- 3. Criar tabelas financeiras
CALL CreateRecibosTables();


-- 4. Limpeza (Remover Procedures temporarias)
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
DROP PROCEDURE IF EXISTS CreateRecibosTables;
DROP PROCEDURE IF EXISTS DropIndexIfExists;

SELECT '🎉 Atualização de banco IDEMPOTENTE concluída!' AS final_status;
