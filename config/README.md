# Configuração de Banco de Dados - Sistema Unificado

## Arquivo de Configuração Centralizada

O sistema agora utiliza um arquivo de configuração centralizada localizado em:
```
config/database.php
```

## Como Alterar as Configurações do Banco

Para fazer deploy da aplicação ou alterar as configurações do banco de dados, edite **apenas** o arquivo `config/database.php`:

```php
// Configurações do banco de dados
define('DB_HOST', 'localhost');           // Host do banco
define('DB_NAME', 'u894209272_planos_aula'); // Nome do banco
define('DB_USER', 'root');                // Usuário
define('DB_PASS', '');                    // Senha
define('DB_CHARSET', 'utf8');             // Charset
```

## Sistema de Redirecionamento Inteligente

O sistema agora inclui funções automáticas para detectar o ambiente e corrigir caminhos:

### Funções Disponíveis:
- `getBaseUrl()` - Detecta automaticamente se está em produção ou desenvolvimento
- `redirectTo($path)` - Redireciona com caminho correto para o ambiente atual

### Detecção Automática:
- **Produção**: Detecta domínio `colegiorosadesharom.com.br` e usa caminho raiz
- **Desenvolvimento**: Detecta pasta `/aulas/` e ajusta caminhos automaticamente

### Exemplo de Uso:
```php
// Em vez de:
header('Location: login.php');

// Use:
require_once __DIR__ . '/config/database.php';
redirectTo('login.php');
```

## Arquivos Atualizados

Todos os arquivos de conexão foram unificados para usar a configuração centralizada:

- `db.php` (raiz)
- `secretaria/partials/db.php`
- `professor/partials/db.php`

**Todos os redirecionamentos foram corrigidos automaticamente** para funcionar tanto em desenvolvimento quanto em produção.

## Vantagens

✅ **Deploy Simplificado**: Alterar apenas um arquivo para configurar o banco  
✅ **Manutenção Centralizada**: Todas as configurações em um local  
✅ **Consistência**: Mesmas configurações em todo o sistema  
✅ **Segurança**: Configurações isoladas em arquivo específico  
✅ **Redirecionamento Inteligente**: Funciona automaticamente em qualquer ambiente  
✅ **Zero Configuração**: Detecta ambiente automaticamente  

## Como Usar

Os arquivos PHP continuam funcionando normalmente, apenas incluindo:
```php
include 'db.php'; // ou o caminho apropriado
```

A variável `$pdo` estará disponível automaticamente com todas as configurações aplicadas.

### Para Redirecionamentos:
```php
require_once __DIR__ . '/config/database.php';
redirectTo('caminho/destino.php');
```
