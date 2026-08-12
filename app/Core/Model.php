<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Lightweight base model with common query helpers.
 */
abstract class Model
{
    /** @var string Database table name. */
    protected string $table = '';

    /** @var array<string, mixed> Attribute cache. */
    protected array $attributes = [];

    /** @var array<int, string> Columns that can be mass assigned. */
    protected array $fillable = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable, true) || $this->fillable === []) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function save(): bool
    {
        if (isset($this->attributes['id'])) {
            return $this->update($this->attributes['id'], $this->attributes);
        }
        return $this->create($this->attributes) !== 0;
    }

    public function create(array $data): int
    {
        if ($this->hasColumn('created_at')) {
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        }
        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        }

        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->table,
            implode('`, `', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        Database::run($sql, array_values($data));
        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        }
        unset($data['id'], $data['created_at']);

        if (empty($data)) {
            return false;
        }

        $set = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        Database::run("UPDATE `{$this->table}` SET {$set} WHERE id = ?", $params);
        return true;
    }

    public static function find(int $id): ?static
    {
        $instance = new static();
        $row = Database::fetch("SELECT * FROM `{$instance->table}` WHERE id = ?", [$id]);
        return $row ? new static($row) : null;
    }

    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $instance = new static();
        $rows = Database::fetchAll("SELECT * FROM `{$instance->table}` WHERE deleted_at IS NULL ORDER BY `{$orderBy}` {$direction}");
        return array_map(fn ($r) => new static($r), $rows);
    }

    public static function where(string $column, mixed $value): array
    {
        $instance = new static();
        $rows = Database::fetchAll("SELECT * FROM `{$instance->table}` WHERE `{$column}` = ? AND deleted_at IS NULL", [$value]);
        return array_map(fn ($r) => new static($r), $rows);
    }

    public function delete(): bool
    {
        if ($this->hasColumn('deleted_at')) {
            Database::run("UPDATE `{$this->table}` SET deleted_at = NOW() WHERE id = ?", [$this->attributes['id']]);
        } else {
            Database::run("DELETE FROM `{$this->table}` WHERE id = ?", [$this->attributes['id']]);
        }
        return true;
    }

    public function hasColumn(string $column): bool
    {
        $stmt = Database::run("SHOW COLUMNS FROM `{$this->table}` LIKE ?", [$column]);
        return $stmt->fetch() !== false;
    }
}
