<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Database
{
    private PDO $pdo;

    public function __construct(?string $configPath = null)
    {
        $configPath ??= dirname(__DIR__, 2) . '/config.php';

        if (!is_file($configPath)) {
            throw new RuntimeException("No se encontró el archivo de configuración: {$configPath}");
        }

        $config = require $configPath;

        if (!is_array($config) || !isset($config['db']) || !is_array($config['db'])) {
            throw new RuntimeException("config.php debe devolver un array con la clave 'db'.");
        }

        $db = $config['db'];

        foreach (['host', 'dbname', 'user', 'pass'] as $key) {
            if (!array_key_exists($key, $db)) {
                throw new RuntimeException("Falta la clave '{$key}' en la configuración 'db'.");
            }
        }

        $charset = $db['charset'] ?? 'utf8mb4';
        $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Error al conectar con la base de datos: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params)->fetchAll();

        return $rows === [] ? null : $rows;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
}