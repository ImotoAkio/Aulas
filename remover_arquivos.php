<?php
/**
 * Script para remover arquivos não utilizados
 */

echo "🗑️ REMOVENDO ARQUIVOS NÃO UTILIZADOS\n\n";

// Arquivos para remover
$arquivos_para_remover = [
    // Scripts de migração e importação
    'analisar_estrutura_local.php',
    'comparar_estruturas.php',
    'executar_migracao.php',
    'importar_corrigido.php',
    'importar_dados_antigos.php',
    'importar_dados_restantes.php',
    'importar_manual.php',
    'importar_mysql_direto.php',
    'importar_simples.php',
    'importar_via_php.php',
    'recriar_banco_seguro.php',
    'solucao_final.php',
    'verificar_importacao.php',
    'verificar_usuarios.php',
    
    // Arquivos de backup SQL
    'backup_completo_local_2025-10-02_21-01-59.sql',
    'backup_final_2025-10-02_21-03-18.sql',
    'backup_php_2025-10-02_21-02-35.sql',
    
    // Arquivo de migração SQL
    'migracao_estrutura.sql',
    
    // Arquivo de documentação de migração
    'GUIA_MIGRACAO.md',
    
    // Arquivos duplicados ou não utilizados
    'planos copy.php',
    'cord.php',
    'aprovados.php',
    'cadastro.php',
    
    // Arquivos de configuração não utilizados
    'package.json',
    'package-lock.json',
    'LICENSE',
    'RESPONSIVE_README.md'
];

// Diretórios para remover
$diretorios_para_remover = [
    'pages/',
    'src/'
];

$removidos = 0;
$erros = 0;
$espaco_liberado = 0;

echo "📄 REMOVENDO ARQUIVOS:\n";
foreach ($arquivos_para_remover as $arquivo) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        if (unlink($arquivo)) {
            echo "✅ Removido: $arquivo (" . formatBytes($tamanho) . ")\n";
            $removidos++;
            $espaco_liberado += $tamanho;
        } else {
            echo "❌ Erro ao remover: $arquivo\n";
            $erros++;
        }
    } else {
        echo "⚠️ Não encontrado: $arquivo\n";
    }
}

echo "\n📁 REMOVENDO DIRETÓRIOS:\n";
foreach ($diretorios_para_remover as $diretorio) {
    if (is_dir($diretorio)) {
        $tamanho = getDirSize($diretorio);
        if (removeDirectory($diretorio)) {
            echo "✅ Removido: $diretorio (" . formatBytes($tamanho) . ")\n";
            $removidos++;
            $espaco_liberado += $tamanho;
        } else {
            echo "❌ Erro ao remover: $diretorio\n";
            $erros++;
        }
    } else {
        echo "⚠️ Não encontrado: $diretorio\n";
    }
}

echo "\n📊 RESUMO DA LIMPEZA:\n";
echo "✅ Arquivos/diretórios removidos: $removidos\n";
echo "❌ Erros encontrados: $erros\n";
echo "💾 Espaço liberado: " . formatBytes($espaco_liberado) . "\n";

if ($erros == 0) {
    echo "\n🎉 LIMPEZA CONCLUÍDA COM SUCESSO!\n";
    echo "O projeto está mais limpo e organizado.\n";
} else {
    echo "\n⚠️ LIMPEZA CONCLUÍDA COM ALGUNS ERROS.\n";
    echo "Alguns arquivos não puderam ser removidos.\n";
}

// Remover este script também
echo "\n🧹 Removendo script de limpeza...\n";
if (unlink(__FILE__)) {
    echo "✅ Script de limpeza removido.\n";
} else {
    echo "⚠️ Não foi possível remover o script de limpeza.\n";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

function getDirSize($directory) {
    $size = 0;
    if (is_dir($directory)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    }
    return $size;
}

function removeDirectory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    removeDirectory($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        return rmdir($dir);
    }
    return false;
}
?>
