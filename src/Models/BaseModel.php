<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;

abstract class BaseModel
{
    /**
     * @param Database $db    Conexión a la base de datos
     * @param string   $table Tabla sobre la que opera el modelo
     */
    public function __construct(
        protected Database $db,
        protected string $table
    ) {
        // Validamos una sola vez; así toda consulta usa un nombre de tabla seguro
        $this->quoteIdentifier($table);
    }

    /**
     * Devuelve todos los registros de la tabla, o null si está vacía.
     */
    public function getAll(): ?array
    {
        $sql = 'SELECT * FROM ' . $this->quoteIdentifier($this->table);

        return $this->db->fetchAll($sql);
    }

    /**
     * Devuelve el registro con ese id, o null si no existe.
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . $this->quoteIdentifier($this->table) . ' WHERE id = ?';

        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Elimina el registro con ese id.
     */
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . $this->quoteIdentifier($this->table) . ' WHERE id = ?';

        $this->db->query($sql, [$id]);
    }

    /**
     * Inserta un registro a partir de pares campo => valor.
     *
     * @return int Id del registro recién creado
     */
    public function insert(array $data): int
    {
        $this->assertNotEmpty($data);

        $columns      = array_map([$this, 'quoteIdentifier'], array_keys($data));
        $placeholders = array_fill(0, count($data), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($this->table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->query($sql, array_values($data));

        return (int) $this->db->lastInsertId();
    }

    /**
     * Modifica el registro $id con pares campo => valor.
     */
    public function update(int $id, array $data): void
    {
        $this->assertNotEmpty($data);

        $assignments = array_map(
            fn (string $column): string => $this->quoteIdentifier($column) . ' = ?',
            array_keys($data)
        );

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = ?',
            $this->quoteIdentifier($this->table),
            implode(', ', $assignments)
        );

        $params   = array_values($data);
        $params[] = $id;

        $this->db->query($sql, $params);
    }

    /**
     * Los nombres de tabla y columna no se pueden enviar como parámetros
     * de un prepared statement, así que se validan y se escapan con backticks.
     * Esto evita inyección SQL a través de las claves del array $data.
     */
    private function quoteIdentifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Identificador SQL no válido: '{$name}'");
        }

        return '`' . $name . '`';
    }

    private function assertNotEmpty(array $data): void
    {
        if ($data === []) {
            throw new InvalidArgumentException('El array de datos no puede estar vacío.');
        }
    }
}