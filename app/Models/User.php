<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::connection();
        $sql = 'SELECT u.id, u.full_name, u.email, u.password_hash, o.name AS organization_name, r.code AS role_code
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organizations o ON o.id = u.organization_id
                WHERE u.email = :email AND u.is_active = 1
                LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
