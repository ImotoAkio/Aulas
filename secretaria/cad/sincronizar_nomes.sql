-- Script SQL para sincronizar o campo 'nome' com 'nome_completo'
-- Execute este script no banco de dados para sincronizar os nomes

-- Atualizar o campo 'nome' com 'nome_completo' onde necessário
UPDATE alunos 
SET nome = nome_completo 
WHERE nome_completo IS NOT NULL 
  AND nome_completo != '' 
  AND (nome != nome_completo OR nome IS NULL OR nome = '');

-- Verificar quantos registros foram atualizados
SELECT 
    COUNT(*) as total_alunos,
    SUM(CASE WHEN nome_completo IS NOT NULL AND nome_completo != '' THEN 1 ELSE 0 END) as com_nome_completo,
    SUM(CASE WHEN nome_completo IS NOT NULL AND nome_completo != '' AND nome = nome_completo THEN 1 ELSE 0 END) as sincronizados,
    SUM(CASE WHEN nome_completo IS NOT NULL AND nome_completo != '' AND (nome != nome_completo OR nome IS NULL OR nome = '') THEN 1 ELSE 0 END) as para_sincronizar
FROM alunos;

-- Mostrar exemplos de alunos que ainda precisam ser sincronizados (se houver)
SELECT 
    id, 
    nome, 
    nome_completo,
    CASE 
        WHEN nome IS NULL OR nome = '' THEN 'Nome vazio'
        WHEN nome != nome_completo THEN 'Nomes diferentes'
        ELSE 'OK'
    END as status
FROM alunos 
WHERE nome_completo IS NOT NULL 
  AND nome_completo != '' 
  AND (nome != nome_completo OR nome IS NULL OR nome = '')
LIMIT 10;
