#!/bin/bash

# ============================================================
# SCRIPT DE ATUALIZAÇÃO VPS (Production Safe)
# ============================================================

# Função para carregar variáveis do .env
load_env() {
    if [ -f .env ]; then
        echo "📄 Carregando variáveis do .env..."
        export $(grep -v '^#' .env | xargs)
    else
        echo "⚠️  Arquivo .env não encontrado!"
    fi
}

# Configurações (Valores padrão ou do .env)
load_env

# Se as variaveis nao vierem do .env, usa os fallbacks (hardcoded ou vazios)
DB_USER=${DB_USER:-"root"}
DB_PASS=${DB_PASS:-""} 
DB_NAME=${DB_NAME:-"sistema_rosa"}
BRANCH="main"

BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)

echo "========================================================"
echo "🚀 INICIANDO ATUALIZAÇÃO: $DATE"
echo "========================================================"

# Verificar se mysql client está instalado
if ! command -v mysql &> /dev/null; then
    echo "❌ Erro: Cliente MySQL 'mysql' não encontrado."
    exit 1
fi

# 1. Criar diretório de backup
echo "📂 Verificando diretório de backup..."
mkdir -p $BACKUP_DIR

# 2. Backup do Banco de Dados
echo "📦 2/6: Fazendo backup do banco de dados ($DB_NAME)..."
if mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_backup_$DATE.sql"; then
    echo "   ✅ Backup SQL salvo em $BACKUP_DIR/db_backup_$DATE.sql"
else
    echo "   ❌ Falha no backup do banco! Abortando."
    exit 1
fi

# 3. Backup dos Arquivos
echo "📦 3/6: Fazendo backup dos arquivos..."
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" . --exclude='./backups' --exclude='./.git' --exclude='./vendor' --exclude='./node_modules' 2>/dev/null
echo "   ✅ Backup arquivos salvo em $BACKUP_DIR/files_backup_$DATE.tar.gz"

# 4. Atualizar Código (Via Git)
echo "⬇️  4/6: Baixando atualizações do Git ($BRANCH)..."
if [ -d ".git" ]; then
    git stash
    if git pull origin $BRANCH; then
        echo "   ✅ Código atualizado."
    else
        echo "   ❌ Erro ao fazer git pull."
        git stash pop
        exit 1
    fi
    git stash pop 2>/dev/null || true
else
    echo "   ⚠️  Não é um repositório git. Pulando atualização de código."
fi

# 5. Rodar Migração de Banco de Dados (SAFE UPDATE)
echo "🗄️  5/6: Atualizando estrutura do banco (MIGRAÇÃO DE SEGURANÇA)..."
if mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < SQL_SAFE_UPDATE.sql; then
    echo "   ✅ Migração executada com sucesso!"
else
    echo "   ❌ Erro na migração do banco de dados."
    exit 1
fi

# 6. Ajustar Permissões (Opcional, mas recomendado)
echo "🔒 6/6: Ajustando permissões (www-data)..."
# Tenta ajustar apenas se o usuario www-data existir
if id "www-data" &>/dev/null; then
    chown -R www-data:www-data .
    chmod -R 755 .
    echo "   ✅ Permissões ajustadas."
else
    echo "   ℹ️  Usuário www-data não encontrado. Pulando ajuste de permissões."
fi

echo "========================================================"
echo "✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!"
echo "========================================================"