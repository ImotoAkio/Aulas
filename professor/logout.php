<?php
/**
 * logout.php
 *
 * Esta página é responsável por encerrar a sessão do usuário.
 * Destrói todas as variáveis de sessão e redireciona para a página de login.
 */

session_start(); // Inicia a sessão existente

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se a sessão for gerenciada por cookies, é importante
// que eles também sejam destruídos.
// Nota: Isso destruirá o cookie de sessão e não apenas os dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona o usuário para a página de login
header('Location: ../login.php'); // Ajuste o caminho se a página de login não estiver no diretório raiz
exit(); // Garante que nenhum outro código seja executado após o redirecionamento

?>
