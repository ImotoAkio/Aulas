<?php
/**
 * SCRIPT DE DEPLOY SEGURO
 * Automatiza o processo de atualização em produção
 */

class DeploySeguro {
    private $config;
    
    public function __construct() {
        $this->config = [
            'backup_dir' => './backups/',
            'temp_dir' => './temp_deploy/',
            'production_url' => 'https://app.colegiorosadesharom.com.br/',
            'local_path' => 'F:/GITHUB/Aulas/',
            'files_to_exclude' => [
                'migrar_banco.php',
                'comparar_banco.php',
                'deploy_seguro.php',
                'teste_*.php',
                'backups/',
                'temp_deploy/',
                '.git/',
                '.gitignore',
                'README.md'
            ]
        ];
    }
    
    /**
     * Executa deploy completo
     */
    public function executarDeploy() {
        echo "<h2>🚀 DEPLOY SEGURO PARA PRODUÇÃO</h2>";
        
        try {
            // 1. Backup do banco
            $this->fazerBackupBanco();
            
            // 2. Backup dos arquivos
            $this->fazerBackupArquivos();
            
            // 3. Preparar arquivos
            $this->prepararArquivos();
            
            // 4. Executar migração do banco
            $this->executarMigracaoBanco();
            
            // 5. Instruções de upload
            $this->mostrarInstrucoesUpload();
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erro no deploy: " . $e->getMessage() . "</div>";
        }
    }
    
    /**
     * Faz backup do banco de dados
     */
    private function fazerBackupBanco() {
        echo "<h3>💾 Fazendo backup do banco de dados...</h3>";
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_file = $this->config['backup_dir'] . "backup_banco_$timestamp.sql";
        
        // Criar diretório se não existir
        if (!is_dir($this->config['backup_dir'])) {
            mkdir($this->config['backup_dir'], 0755, true);
        }
        
        // Comando mysqldump (ajustar conforme sua configuração)
        $comando = "mysqldump -u root -p --single-transaction --routines --triggers nome_banco > $backup_file";
        
        echo "<div style='background-color: #e7f3ff; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Comando para backup:</strong><br>";
        echo "<code>$comando</code><br><br>";
        echo "<strong>Ou execute manualmente:</strong><br>";
        echo "1. Acesse o painel de controle da hospedagem<br>";
        echo "2. Vá em 'Backup do Banco de Dados'<br>";
        echo "3. Faça download do backup completo<br>";
        echo "4. Guarde em local seguro<br>";
        echo "</div>";
    }
    
    /**
     * Faz backup dos arquivos atuais
     */
    private function fazerBackupArquivos() {
        echo "<h3>📁 Fazendo backup dos arquivos atuais...</h3>";
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_dir = $this->config['backup_dir'] . "backup_arquivos_$timestamp/";
        
        echo "<div style='background-color: #e7f3ff; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Instruções para backup dos arquivos:</strong><br>";
        echo "1. Acesse o painel de controle da hospedagem<br>";
        echo "2. Vá em 'Gerenciador de Arquivos'<br>";
        echo "3. Selecione todos os arquivos da aplicação<br>";
        echo "4. Crie um arquivo ZIP com backup<br>";
        echo "5. Baixe e guarde em local seguro<br>";
        echo "</div>";
    }
    
    /**
     * Prepara arquivos para upload
     */
    private function prepararArquivos() {
        echo "<h3>📦 Preparando arquivos para upload...</h3>";
        
        $temp_dir = $this->config['temp_dir'];
        
        // Criar diretório temporário
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        // Listar arquivos para upload
        $arquivos = $this->listarArquivosParaUpload();
        
        echo "<div style='background-color: #f0f8ff; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Arquivos que serão atualizados:</strong><br>";
        echo "<ul>";
        foreach ($arquivos as $arquivo) {
            echo "<li>$arquivo</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // Criar ZIP para upload
        $this->criarZipUpload($arquivos);
    }
    
    /**
     * Lista arquivos para upload
     */
    private function listarArquivosParaUpload() {
        $arquivos = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->config['local_path'])
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative_path = str_replace($this->config['local_path'], '', $file->getPathname());
                $relative_path = str_replace('\\', '/', $relative_path);
                
                // Verificar se deve ser excluído
                $excluir = false;
                foreach ($this->config['files_to_exclude'] as $pattern) {
                    if (fnmatch($pattern, $relative_path)) {
                        $excluir = true;
                        break;
                    }
                }
                
                if (!$excluir) {
                    $arquivos[] = $relative_path;
                }
            }
        }
        
        return $arquivos;
    }
    
    /**
     * Cria ZIP para upload
     */
    private function criarZipUpload($arquivos) {
        $zip_file = $this->config['temp_dir'] . 'deploy_' . date('Y-m-d_H-i-s') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
            foreach ($arquivos as $arquivo) {
                $full_path = $this->config['local_path'] . $arquivo;
                if (file_exists($full_path)) {
                    $zip->addFile($full_path, $arquivo);
                }
            }
            $zip->close();
            
            echo "<div style='background-color: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "✅ <strong>Arquivo ZIP criado:</strong> $zip_file<br>";
            echo "📁 Tamanho: " . $this->formatarTamanho(filesize($zip_file)) . "<br>";
            echo "</div>";
        } else {
            echo "<div style='color: red;'>❌ Erro ao criar arquivo ZIP</div>";
        }
    }
    
    /**
     * Executa migração do banco
     */
    private function executarMigracaoBanco() {
        echo "<h3>🔄 Executando migração do banco...</h3>";
        
        echo "<div style='background-color: #fff3cd; padding: 10px; border-radius: 5px;'>";
        echo "<strong>⚠️ IMPORTANTE - Migração do Banco:</strong><br>";
        echo "1. Faça upload do arquivo <code>migrar_banco.php</code> primeiro<br>";
        echo "2. Acesse: <code>{$this->config['production_url']}migrar_banco.php</code><br>";
        echo "3. Execute a migração<br>";
        echo "4. Verifique se todas as tabelas e colunas foram criadas<br>";
        echo "5. <strong>APENAS DEPOIS</strong> faça upload dos outros arquivos<br>";
        echo "</div>";
    }
    
    /**
     * Mostra instruções de upload
     */
    private function mostrarInstrucoesUpload() {
        echo "<h3>📤 Instruções de Upload</h3>";
        
        echo "<div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🎯 ORDEM CORRETA DE DEPLOY:</h4>";
        echo "<ol>";
        echo "<li><strong>1. Backup:</strong> Faça backup do banco e arquivos atuais</li>";
        echo "<li><strong>2. Migração:</strong> Upload e execução do <code>migrar_banco.php</code></li>";
        echo "<li><strong>3. Arquivos:</strong> Upload dos arquivos PHP atualizados</li>";
        echo "<li><strong>4. Teste:</strong> Verificar se tudo está funcionando</li>";
        echo "<li><strong>5. Configuração:</strong> Ajustar URLs de webhook para produção</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🚨 CHECKLIST DE SEGURANÇA:</h4>";
        echo "<ul>";
        echo "<li>✅ Backup do banco feito</li>";
        echo "<li>✅ Backup dos arquivos feito</li>";
        echo "<li>✅ Migração do banco executada</li>";
        echo "<li>✅ Arquivos PHP atualizados</li>";
        echo "<li>✅ URLs de webhook configuradas</li>";
        echo "<li>✅ Testes de funcionalidade realizados</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🔧 CONFIGURAÇÕES PÓS-DEPLOY:</h4>";
        echo "<ol>";
        echo "<li>Acesse <code>secretaria/configuracoes.php</code></li>";
        echo "<li>Configure URLs de webhook para produção</li>";
        echo "<li>Acesse <code>financeiro/configuracoes.php</code></li>";
        echo "<li>Configure URLs de webhook para produção</li>";
        echo "<li>Teste funcionalidades de pré-cadastro</li>";
        echo "<li>Teste webhooks de aprovação</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    /**
     * Formata tamanho de arquivo
     */
    private function formatarTamanho($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Executar deploy se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'deploy_seguro.php') {
    $deploy = new DeploySeguro();
    $deploy->executarDeploy();
}
?>
