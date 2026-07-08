<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table;

    protected function db(): PDO
    {
        return Database::connection();
    }

    public function all(string $orderBy = 'id desc'): array
    {
        return $this->db()->query("select * from {$this->table} order by {$orderBy}")->fetchAll();
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $fields = implode(', ', $columns);
        $params = ':' . implode(', :', $columns);
        $statement = $this->db()->prepare("insert into {$this->table} ({$fields}) values ({$params})");
        $statement->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function countWhere(string $where = '1=1', array $params = []): int
    {
        $statement = $this->db()->prepare("select count(*) from {$this->table} where {$where}");
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }
}
