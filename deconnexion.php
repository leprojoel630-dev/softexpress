<?php
declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Destruction de la session
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| Suppression du cookie de session
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

/*
|--------------------------------------------------------------------------
| Destruction complète de la session
|--------------------------------------------------------------------------
*/

session_destroy();

/*
|--------------------------------------------------------------------------
| Redirection vers la connexion
|--------------------------------------------------------------------------
*/

header('Location: connexion.php');
exit;