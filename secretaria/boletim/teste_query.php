<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php'; // Conexão com o banco

$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);
$periodo = filter_input(INPUT_GET, 'periodo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$aluno_id || empty($periodo)) {
    die("Parâmetros 'aluno_id' e 'periodo' são obrigatórios.");
}

echo "Iniciando testes de consulta...<br><br>";

try {
// ****** NÓS VAMOS COLOCAR O CÓDIGO DE TESTE AQUI DENTRO ******
echo "<b>TESTANDO CONSULTA 3 (PARECERES)...</b><br>";

$stmt = $pdo->prepare("SELECT p.*, u.nome AS nome_professor FROM pareceres p JOIN usuarios u ON p.id_professor_designado = u.id WHERE p.id_aluno = :id_aluno AND p.periodo = :periodo ORDER BY u.nome");
$stmt->execute([':id_aluno' => $aluno_id, ':periodo' => $periodo]);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<b>CONSULTA 3 (PARECERES) OK!</b><br>";
print_r($resultado);


} catch (PDOException $e) {
    die("<b>ERRO ENCONTRADO!</b> A consulta falhou com a seguinte mensagem: <br><pre>" . $e->getMessage() . "</pre>");
}
?>