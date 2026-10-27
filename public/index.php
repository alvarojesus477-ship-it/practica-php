<?php

declare(strict_types=1);

/**
 * Front controller del Tablón de anuncios.
 * Todas las peticiones entran por aquí y se reparten a un controlador.
 */

use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Core\Database;
use App\Models\Post;
use App\Models\User;

// ------------------------------------------------------------
// Configuración básica
// ------------------------------------------------------------

// En desarrollo se muestran los errores; ponlo a false en producción
const DEBUG = true;

define('ROOT_PATH', dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');


require ROOT_PATH . '/vendor/autoload.php';

// ------------------------------------------------------------
// Sesión (con cookie más segura)
// ------------------------------------------------------------

session_set_cookie_params([
    'httponly' => true,    // JavaScript no puede leer la cookie de sesión
    'samesite' => 'Lax',   // no se envía en peticiones POST desde otros sitios
]);
session_start();

// ------------------------------------------------------------
// Rutas: [método, patrón, controlador, acción]
// {id} se sustituye por un número y se pasa a la acción como int
// ------------------------------------------------------------

$routes = [
    // Anuncios
    ['GET',  '/',                    PostController::class, 'index'],
    ['GET',  '/posts',               PostController::class, 'index'],
    ['GET',  '/posts/create',        PostController::class, 'create'],
    ['POST', '/posts/store',         PostController::class, 'store'],
    ['GET',  '/posts/{id}',          PostController::class, 'show'],
    ['GET',  '/posts/{id}/edit',     PostController::class, 'edit'],
    ['POST', '/posts/{id}/update',   PostController::class, 'update'],
    ['POST', '/posts/{id}/delete',   PostController::class, 'destroy'],

    // Autenticación
    ['GET',  '/register',            AuthController::class, 'showRegisterForm'],
    ['POST', '/register',            AuthController::class, 'register'],
    ['GET',  '/login',               AuthController::class, 'showLoginForm'],
    ['POST', '/login',               AuthController::class, 'login'],
    ['POST', '/logout',              AuthController::class, 'logout'],
];

// ------------------------------------------------------------
// Auxiliares
// ------------------------------------------------------------

/**
 * Crea un controlador con sus dependencias (una sola conexión a la BD por petición).
 */
function makeController(string $class): object
{
    static $db = null;
    $db ??= new Database();

    return match ($class) {
        AuthController::class => new AuthController(new User($db)),
        PostController::class => new PostController(new Post($db)),
    };
}

function abortWith(int $code, string $message): never
{
    http_response_code($code);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error ' . $code . '</title></head><body>'
        . '<h1>Error ' . $code . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><a href="/posts">Volver al tablón</a></p></body></html>';

    exit;
}

/** Escapa texto antes de volcarlo al HTML (previene XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------
// Despacho de la petición
// ------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path   = $path === '/' ? '/' : rtrim($path, '/');

try {
    $pathExists = false;

    foreach ($routes as [$routeMethod, $pattern, $controllerClass, $action]) {
        $regex = '#^' . preg_replace('#\{[a-z_]+\}#', '(\d+)', $pattern) . '$#';

        if (!preg_match($regex, $path, $matches)) {
            continue;
        }

        $pathExists = true;

        if ($routeMethod !== $method) {
            continue;
        }

        array_shift($matches); // quitamos la coincidencia completa
        $params = array_map('intval', $matches);

        makeController($controllerClass)->$action(...$params);

        return;
    }

    if ($pathExists) {
        abortWith(405, 'Método no permitido.');
    }

    abortWith(404, 'La página que buscas no existe.');
}

catch (Throwable $e) {
    error_log((string) $e);

    abortWith(500, DEBUG ? $e->getMessage() : 'Ha ocurrido un error inesperado.');
}