<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use PDOException;

class AuthController
{
    public function __construct(private User $users)
    {
    }

    // ------------------------------------------------------------
    // Registro
    // ------------------------------------------------------------

    public function showRegisterForm(): void
    {
        $this->render('auth/register');
    }

    public function register(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];

        if ($username === '' || mb_strlen($username) > 50) {
            $errors[] = 'El nombre de usuario es obligatorio (máximo 50 caracteres).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($errors === []) {
            try {
                $this->users->insert([
                    'username' => $username,
                    'email'    => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $this->redirect('/login');
            } catch (PDOException $e) {
                // 23000 = violación de restricción de integridad (UNIQUE en username/email)
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                $errors[] = 'El nombre de usuario o el email ya están registrados.';
            }
        }

        // Volvemos a mostrar el formulario con los errores (y sin la contraseña)
        $this->render('auth/register', [
            'errors' => $errors,
            'old'    => ['username' => $username, 'email' => $email],
        ]);
    }

    // ------------------------------------------------------------
    // Login / logout
    // ------------------------------------------------------------

    public function showLoginForm(): void
    {
        $this->render('auth/login');
    }

    public function login(): void
    {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $userId = $this->users->findByEmail($email, $password);

        if ($userId === null) {
            // Mismo mensaje para "email inexistente" y "contraseña incorrecta"
            $this->render('auth/login', [
                'errors' => ['Email o contraseña incorrectos.'],
                'old'    => ['email' => $email],
            ]);

            return;
        }

        $this->startSession();

        // Evita la fijación de sesión: nuevo identificador tras autenticarse
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['email']   = $email;

        $this->redirect('/');
    }

    public function logout(): void
    {
        $this->startSession();

        $_SESSION = [];

        // Elimina también la cookie de sesión del navegador
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

        session_destroy();

        $this->redirect('/login');
    }

    // ------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Incluye una vista de /views pasándole variables.
     * Ejemplo: render('auth/login', ['errors' => [...]]) -> views/auth/login.php
     */
    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require dirname(__DIR__, 2) . "/views/{$view}.php";
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}
