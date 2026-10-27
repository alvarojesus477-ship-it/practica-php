<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;

class PostController
{
    public function __construct(private Post $posts)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ------------------------------------------------------------
    // Lectura (públicas)
    // ------------------------------------------------------------

    /**
     * Listado de posts. Si llega ?q=texto, filtra por título o categoría.
     */
    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));

        $posts = $search === ''
            ? $this->posts->getAllWithAuthor()
            : $this->posts->search($search);

        $this->render('posts/index', [
            'posts'  => $posts,
            'search' => $search,
        ]);
    }

    /**
     * Muestra un único post (el id llega por la URL).
     */
    public function show(int $id): void
    {
        $post = $this->posts->getByIdWithAuthor($id);

        if ($post === null) {
            $this->flash('error', 'La publicación no existe.');
            $this->redirect('/posts');
        }

        $this->render('posts/show', ['post' => $post]);
    }

    // ------------------------------------------------------------
    // Crear (requiere login)
    // ------------------------------------------------------------

    public function create(): void
    {
        $this->requireLogin('Debes iniciar sesión para publicar un anuncio.');

        $this->render('posts/form', ['post' => null, 'old' => null, 'errors' => []]);
    }

    public function store(): void
    {
        $userId = $this->requireLogin('Debes iniciar sesión para publicar un anuncio.');

        $data   = $this->collectInput();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->render('posts/form', ['post' => null, 'old' => $data, 'errors' => $errors]);

            return;
        }

        // El autor sale SIEMPRE de la sesión, nunca del formulario
        $id = $this->posts->insert($data + ['user_id' => $userId]);

        $this->flash('success', 'Anuncio publicado correctamente.');
        $this->redirect('/posts/' . $id);
    }

    // ------------------------------------------------------------
    // Editar / borrar (requiere login + ser el autor)
    // ------------------------------------------------------------

    public function edit(int $id): void
    {
        $post = $this->findOwnedPost($id);

        $this->render('posts/form', ['post' => $post, 'old' => null, 'errors' => []]);
    }

    public function update(int $id): void
    {
        $post = $this->findOwnedPost($id);

        $data   = $this->collectInput();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->render('posts/form', ['post' => $post, 'old' => $data, 'errors' => $errors]);

            return;
        }

        $this->posts->update($id, $data);

        $this->flash('success', 'Anuncio actualizado correctamente.');
        $this->redirect('/posts/' . $id);
    }

    public function destroy(int $id): void
    {
        $this->findOwnedPost($id);

        $this->posts->delete($id);

        $this->flash('success', 'Anuncio eliminado.');
        $this->redirect('/posts');
    }

    // ------------------------------------------------------------
    // Autenticación y autorización
    // ------------------------------------------------------------

    /**
     * Autenticación: si no hay sesión iniciada, redirige al login con un mensaje.
     *
     * @return int Id del usuario logueado
     */
    private function requireLogin(string $message = 'Debes iniciar sesión para realizar esta acción.'): int
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId === null) {
            $this->flash('error', $message);
            $this->redirect('/login');
        }

        return (int) $userId;
    }

    /**
     * Autorización: devuelve el post solo si existe y pertenece al usuario de la sesión.
     */
    private function findOwnedPost(int $id): array
    {
        $userId = $this->requireLogin('Debes iniciar sesión para modificar o eliminar anuncios.');

        $post = $this->posts->getById($id);

        if ($post === null) {
            $this->flash('error', 'La publicación no existe.');
            $this->redirect('/posts');
        }

        if ((int) $post['user_id'] !== $userId) {
            $this->flash('error', 'No tienes permiso para hacer esto.');
            $this->redirect('/posts');
        }

        return $post;
    }

    // ------------------------------------------------------------
    // Entrada de formulario
    // ------------------------------------------------------------

    /**
     * Recoge solo los campos permitidos (así nadie puede colar user_id, id, etc.).
     */
    private function collectInput(): array
    {
        $category = trim((string) ($_POST['category'] ?? ''));

        return [
            'title'    => trim((string) ($_POST['title'] ?? '')),
            'content'  => trim((string) ($_POST['content'] ?? '')),
            'category' => $category === '' ? 'General' : $category,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['title'] === '' || mb_strlen($data['title']) > 150) {
            $errors[] = 'El título es obligatorio (máximo 150 caracteres).';
        }

        if ($data['content'] === '') {
            $errors[] = 'El contenido es obligatorio.';
        }

        if (mb_strlen($data['category']) > 50) {
            $errors[] = 'La categoría no puede superar los 50 caracteres.';
        }

        return $errors;
    }

    // ------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return $flash;
    }

    private function render(string $view, array $data = []): void
    {
        $data['flash']         = $this->pullFlash();
        $data['currentUserId'] = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        extract($data, EXTR_SKIP);

        require dirname(__DIR__, 2) . "/views/{$view}.php";
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}