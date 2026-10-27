<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Post extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'posts');
    }

    /**
     * Todos los posts con el nombre de su autor, del más reciente al más antiguo.
     */
    public function getAllWithAuthor(): ?array
    {
        return $this->db->fetchAll(
            'SELECT p.*, u.username
               FROM posts p
               JOIN users u ON u.id = p.user_id
              ORDER BY p.created_at DESC'
        );
    }

    /**
     * Un post con el nombre de su autor.
     */
    public function getByIdWithAuthor(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT p.*, u.username
               FROM posts p
               JOIN users u ON u.id = p.user_id
              WHERE p.id = ?',
            [$id]
        );
    }

    /**
     * Busca posts cuyo título contenga el texto indicado.
     */
    /**
 * Busca posts cuyo título o categoría contengan el texto indicado.
 */
public function search(string $term): ?array
{
    // Escapamos los comodines de LIKE para que se busquen como texto literal
    $like = '%' . addcslashes($term, '%_\\') . '%';

    return $this->db->fetchAll(
        'SELECT p.*, u.username
           FROM posts p
           JOIN users u ON u.id = p.user_id
          WHERE p.title LIKE ? OR p.category LIKE ?
          ORDER BY p.created_at DESC',
        [$like, $like]
    );
}

    /**
     * Posts publicados por un usuario.
     */
    public function getByUser(int $userId): ?array
    {
        return $this->db->fetchAll(
            'SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }
}
