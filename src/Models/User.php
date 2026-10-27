<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'users');
    }

    /**
     * Busca un usuario por email y contraseña (útil para el login).
     */
    public function findByEmail(string $email, string $password): ?int
    {
        $user = $this->db->fetchOne(
            'SELECT id, password FROM users WHERE email = ?',
            [$email]
        );
 
        if ($user === null || !password_verify($password, $user['password'])) {
            return null;
        }
 
        return (int) $user['id'];
    }

}
