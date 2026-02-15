#!/bin/bash

# ============================================================
# SCRIPT DE ATUALIZAÇÃO VPS (Docker Support)
# ============================================================

# Função para carregar variáveis do .env
load_env() {
    if [ -f .env ]; then
        echo "📄 Carregando variáveis do .env..."
        export $(grep -v '^#' .env | xargs)
    else
        echo "⚠️  Arquivo .env não encontrado! Usando valores padrão."
    fi
}

load_env

# Configurações (Valores padrão ou do .env)
# Se as variaveis nao vierem do .env, usa os fallbacks
# ATENCAO: Se rodar via Docker, o host para o script (externo) não importa, 
# mas dentro do container o user/pass importam.
DB_USER=${DB_USER:-"root"}
DB_PASS=${DB_PASS:-"Akio2604*"} 
DB_NAME=${DB_NAME:-"sistema_rosa"}
BRANCH="main"

BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)

echo "========================================================"
echo "🚀 INICIANDO ATUALIZAÇÃO (DOCKER MODE): $DATE"
echo "========================================================"

# --- DETECÇÃO DO CONTAINER MYSQL ---
echo "🔍 Buscando container do banco de dados (MariaDB/MySQL)..."
# Tenta encontrar um container que tenha 'mariadb' ou 'mysql' no nome e esteja rodando
DB_CONTAINER=$(docker ps --format "{{.Names}}" | grep -E "mariadb|mysql" | head -n 1)

if [ -z "$DB_CONTAINER" ]; then
    echo "❌ Erro: Nenhum container MySQL/MariaDB encontrado rodando!"
    echo "   Verifique se o banco está subiu (docker ps)."
    exit 1
fi

echo "✅ Container encontrado: $DB_CONTAINER"

# 1. Criar diretório de backup
echo "📂 Verificando diretório de backup..."
mkdir -p $BACKUP_DIR

# 2. Backup do Banco de Dados
echo "📦 2/6: Fazendo backup do banco de dados ($DB_NAME)..."
# Executa mysqldump DENTRO do container
if docker exec "$DB_CONTAINER" mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_backup_$DATE.sql"; then
    echo "   ✅ Backup SQL salvo em $BACKUP_DIR/db_backup_$DATE.sql"
else
    echo "   ❌ Falha no backup do banco! Verifique senha ou nome do banco."
    # Não aborta se for apenas erro de backup? Melhor abortar pra segurança.
    exit 1
fi

# 3. Backup dos Arquivos
echo "📦 3/6: Fazendo backup dos arquivos..."
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" . --exclude='./backups' --exclude='./.git' --exclude='./vendor' --exclude='./node_modules' 2>/dev/null
echo "   ✅ Backup arquivos salvo em $BACKUP_DIR/files_backup_$DATE.tar.gz"

# 4. Atualizar Código (Via Git)
echo "⬇️  4/6: Baixando atualizações do Git ($BRANCH)..."
if [ -d ".git" ]; then
    # Stash local changes to avoid conflicts (like update.sh itself)
    git stash
    if git pull origin $BRANCH; then
        echo "   ✅ Código atualizado."
    else
        echo "   ❌ Erro ao fazer git pull."
        git stash pop 2>/dev/null
        exit 1
    fi
    # Tenta restaurar stash, mas se der conflito, deixa no stash
    git stash pop 2>/dev/null || echo "   ℹ️  Mudanças locais mantidas no stash para evitar conflitos."
else
    echo "   ⚠️  Não é um repositório git. Pulando atualização de código."
    echo "       (Certifique-se de que subiu os arquivos manualmente)"
fi

# 5. Rodar Migração de Banco de Dados (SAFE UPDATE)
echo "🗄️  5/6: Atualizando estrutura do banco (MIGRAÇÃO DE SEGURANÇA)..."

# Precisamos copiar o arquivo SQL para dentro do container ou ler via pipe
# Ler via pipe é mais fácil e não deixa lixo no container
# docker exec -i (interactive) permite passar o arquivo via stdin
if cat SQL_SAFE_UPDATE.sql | docker exec -i "$DB_CONTAINER" mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"; then
    echo "   ✅ Migração executada com sucesso!"
else
    echo "   ❌ Erro na migração do banco de dados."
    exit 1
fi

# 6. Ajustar Permissões (Opcional)
echo "🔒 6/6: Ajustando permissões..."
# Ajusta permissões dos arquivos locais para o usuário atual (root provavelmente)
# Se o container web precisar de permissão específica, teria que ver qual user ele usa.
# Geralmente em setups simples, o volume montado herda permissões ou o docker chown.
# Vamos manter simples.
echo "   ✅ Permissões mantidas."

echo "========================================================"
echo "✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!"
echo "========================================================"