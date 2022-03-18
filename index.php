<?php

use App\Controllers\ArticlesController;
use App\Controllers\ArticlesCommentsController;
use App\Controllers\UsersController;
use App\Redirect;
use App\View;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once "vendor/autoload.php";

session_start();

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [UsersController::class, 'start']);
    $r->addRoute('GET', '/users/register', [UsersController::class, 'showRegister']);
    $r->addRoute('GET', '/users/login', [UsersController::class, 'showLogin']);
    $r->addRoute('POST', '/users/register', [UsersController::class, 'register']);
    $r->addRoute('POST', '/users/login', [UsersController::class, 'login']);

    //articles
    $r->addRoute('GET', '/articles', [ArticlesController::class, 'index']);
    $r->addRoute('GET', '/articles/{id:\d+}', [ArticlesController::class, 'show']);

    $r->addRoute('POST', '/articles', [ArticlesController::class, 'store']);
    $r->addRoute('GET', '/articles/create', [ArticlesController::class, 'create']);

    $r->addRoute('POST', '/articles/{id:\d+}/delete', [ArticlesController::class, 'delete']);
    $r->addRoute('GET', '/articles/{id:\d+}/edit', [ArticlesController::class, 'edit']);
    $r->addRoute('POST', '/articles/{id:\d+}/like', [ArticlesController::class, 'like']);
    $r->addRoute('POST', '/articles/{id:\d+}', [ArticlesController::class, 'update']);
    $r->addRoute('POST', '/articles/{articleId:\d+}/comments', [ArticlesCommentsController::class, 'store']);
    $r->addRoute('POST', '/articles/{articleId:\d+}/comments/{id:\d+}/delete', [ArticlesCommentsController::class, 'delete']);

    $r->addRoute('GET', '/all', [UsersController::class, 'showAllUsers']);
    $r->addRoute('POST', '/add/{userId:\d+}', [UsersController::class, 'addFriend']);
    $r->addRoute('GET', '/friendRequests', [UsersController::class, 'friendRequests']);
    $r->addRoute('GET', '/friends', [UsersController::class, 'friends']);
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        var_dump('404');
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        var_dump('Not allowed');
        break;
    case FastRoute\Dispatcher::FOUND:
        $contr = $routeInfo[1][0];
        $method = $routeInfo[1][1];

        $response = (new $contr)->$method($routeInfo[2]);

        $loader = new FilesystemLoader('app/Views');
        $twig = new Environment($loader);
//        echo "<pre>";
//        var_dump($_SESSION);
//        echo "</pre>";
        if ($response instanceof View) {
            echo $twig->render($response->getPath() . '.html', $response->getVariables());
        }

        if ($response instanceof Redirect)
        {
            header('Location: ' . $response->getLocation());
            exit;
        }

        break;
}

if (isset($_SESSION['errors'])) {
    unset($_SESSION['errors']);
}
if (isset($_SESSION['inputs'])) {
    unset($_SESSION['inputs']);
}