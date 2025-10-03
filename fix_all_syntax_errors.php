<?php
/**
 * Script para corrigir todos os erros de sintaxe PHP no projeto
 */

echo "🔧 Corrigindo erros de sintaxe PHP...\n\n";

// Lista de arquivos com erros identificados
$files_with_errors = [
    // Arquivos do aluno
    'aluno/alterar_senha.php' => 156,
    'aluno/boletim.php' => 146,
    'aluno/declaracoes.php' => 87,
    'aluno/notas.php' => 136,
    'aluno/pareceres.php' => 90,
    'aluno/perfil.php' => 144,
    
    // Arquivos do professor
    'professor/avaliar_aluno.php' => 147,
    'professor/editar_plano.php' => 76,
    'professor/notas.php' => 146,
    'professor/parecer.php' => 97,
    'professor/planos.php' => 71,
    'professor/planos_revisao.php' => 43,
    'professor/ver_notas.php' => 253,
    'professor/partials/_navbar.php' => 52,
    
    // Arquivos da secretaria
    'secretaria/notas.php' => 89,
    'secretaria/parecer.php' => 232,
    'secretaria/planos.php' => 60,
    'secretaria/visualizar_plano.php' => 72,
    'secretaria/partials/_sidebar.php' => 40,
];

$fixed_count = 0;

foreach ($files_with_errors as $file => $line) {
    if (!file_exists($file)) {
        echo "❌ Arquivo não encontrado: $file\n";
        continue;
    }
    
    echo "🔍 Verificando: $file\n";
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Padrões de correção
    $patterns = [
        // Corrigir tags <link> mal fechadas
        '/<link([^>]*?)href="<\?php echo getAssetUrl\("([^"]+)"\); ?>"([^>]*?)"([^>]*?)>/' => '<link$1href="<?php echo getAssetUrl("$2"); ?>"$3>$4',
        
        // Corrigir tags <img> mal fechadas
        '/<img([^>]*?)src="<\?php echo getAssetUrl\("([^"]+)"([^>]*?)alt="([^"]+)"([^>]*?)>/' => '<img$1src="<?php echo getAssetUrl("$2"); ?>"$3alt="$4"$5>',
        
        // Corrigir aspas duplas mal fechadas em getAssetUrl
        '/getAssetUrl\("([^"]+)"([^>]*?)alt="([^"]+)"([^>]*?)>/' => 'getAssetUrl("$1"); ?>"$2alt="$3"$4>',
        
        // Corrigir problemas com barras invertidas em _sidebar.php
        '/getPageUrl\("([^"]+)"\)/' => 'getPageUrl("$1")',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Verificar se houve mudanças
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        echo "✅ Corrigido: $file\n";
        $fixed_count++;
    } else {
        echo "ℹ️  Nenhuma correção necessária: $file\n";
    }
}

echo "\n🎉 Correção concluída!\n";
echo "📊 Arquivos corrigidos: $fixed_count\n";
echo "\n🔍 Verificando sintaxe...\n";

// Verificar sintaxe dos arquivos corrigidos
foreach ($files_with_errors as $file => $line) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $file - OK\n";
        } else {
            echo "❌ $file - Ainda tem erros\n";
            echo "   $output\n";
        }
    }
}

echo "\n✨ Processo finalizado!\n";
?>
