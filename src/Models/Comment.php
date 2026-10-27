<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Comment extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'comments');
    }

    /**
     * Comentarios de un post con el nombre de su autor, del más antiguo al más reciente.
     */
    public function getByPost(int $postId): ?array
    {
        return $this->db->fetchAll(
            'SELECT c.*, u.username
               FROM comments c
               JOIN users u ON u.id = c.user_id
              WHERE c.post_id = ?
              ORDER BY c.created_at ASC',
            [$postId]
        );
    }

    /**
     * Comentarios escritos por un usuario.
     */
    public function getByUser(int $userId): ?array
    {
        return $this->db->fetchAll(
            'SELECT * FROM comments WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }
}
