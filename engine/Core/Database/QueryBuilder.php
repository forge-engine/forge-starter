<?php

declare(strict_types=1);

namespace Forge\Core\Database;

use Forge\Core\DI\Attributes\Service;
use PDO;
use PDOStatement;
use RuntimeException;

#[Service(singleton: false)]
final class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $select = [];
    private array $where = [];
    private array $params = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private array $joins = [];
    private array $groupBy = [];
    private ?string $having = null;
    private ?int $offset = null;
    private bool $inTransaction = false;
    private ?string $createTableSql = null;
    private ?string $dropTableSql = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = "";
    }

    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(string ...$columns): self
    {
        $this->select = $columns;
        return $this;
    }

    private int $paramCounter = 0;

    public function where(string $column, string $operator, mixed $value): self
    {
        $paramName = "param" . $this->paramCounter++;
        $placeholder = ":" . $paramName;
        $this->where[] = "$column $operator $placeholder";
        $this->params[$placeholder] = $value;
        return $this;
    }

    /**
     * Add a where IS NULL clause to the query
     */
    public function whereNull(string $column): self
    {
        $this->where[] = "$column IS NULL";
        return $this;
    }

    /**
     * Add a where IS NOT NULL clause to the query
     */
    public function whereNotNull(string $column): self
    {
        $this->where[] = "$column IS NOT NULL";
        return $this;
    }

    public function orderBy(string $column, string $direction = "ASC"): self
    {
        $this->orderBy = "$column $direction";
        return $this;
    }

    public function limit(int $count): self
    {
        $this->limit = $count;
        return $this;
    }

    public function offset(int $count): self
    {
        $this->offset = $count;
        return $this;
    }

    /**
     * @template T of object
     * @param class-string<T>|null $dtoClass
     * @return array<T>|array<Model>
     */
    public function get(?string $dtoClass = null): array
    {
        $stmt = $this->prepareStatement();
        $stmt->execute($this->params);

        if ($dtoClass !== null) {
            return $this->hydrateAll($stmt, $dtoClass);
        }

        return $stmt->fetchAll(PDO::FETCH_CLASS, Model::class);
    }

    public function insert(array $data): int
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        $set = [];
        foreach ($data as $column => $value) {
            $placeholder = ":" . $column;
            $set[] = "$column = $placeholder";
            $this->params[$placeholder] = $value;
        }
        $sql = "UPDATE $this->table SET " . implode(", ", $set);

        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(" AND ", $this->where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM $this->table";
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(" AND ", $this->where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    /**
     * @template T of object
     * @param class-string<T>|null $dtoClass
     * @return T|Model|null
     */
    public function first(?string $dtoClass = null): object|array|null
    {
        $stmt = $this->prepareStatement();
        $stmt->execute($this->params);

        if ($dtoClass !== null) {
            return $this->hydrate($stmt, $dtoClass);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function find(int $id, string $dtoClass): ?object
    {
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null) {
            throw new RuntimeException(
                "Primary key not found for table: {$this->table}"
            );
        }
        $this->where($primaryKey, "=", $id);
        return $this->first($dtoClass);
    }

    /**
     * Join another table
     */
    public function join(
        string $table,
        string $first,
        string $operator,
        string $second,
        string $type = "INNER"
    ): self {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    /**
     * Left join another table
     */
    public function leftJoin(
        string $table,
        string $first,
        string $operator,
        string $second
    ): self {
        return $this->join($table, $first, $operator, $second, "LEFT");
    }

    /**
     * Right join another table
     */
    public function rightJoin(
        string $table,
        string $first,
        string $operator,
        string $second
    ): self {
        return $this->join($table, $first, $operator, $second, "RIGHT");
    }

    /**
     * Add a GROUP BY clause
     */
    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * Add a HAVING clause
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $paramName = "having" . $this->paramCounter++;
        $placeholder = ":" . $paramName;
        $this->having = "$column $operator $placeholder";
        $this->params[$placeholder] = $value;
        return $this;
    }

    /**
     * Start a database transaction
     */
    public function beginTransaction(): self
    {
        if (!$this->inTransaction) {
            $this->pdo->beginTransaction();
            $this->inTransaction = true;
        }
        return $this;
    }

    /**
     * Commit the active database transaction
     */
    public function commit(): self
    {
        if ($this->inTransaction) {
            $this->pdo->commit();
            $this->inTransaction = false;
        }
        return $this;
    }

    /**
     * Rollback the active database transaction
     */
    public function rollback(): self
    {
        if ($this->inTransaction) {
            $this->pdo->rollBack();
            $this->inTransaction = false;
        }
        return $this;
    }

    /**
     * Execute a function within a transaction
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function getPrimaryKey(): ?string
    {
        $reflection = new \ReflectionClass(Model::class);
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if (
                !empty($attributes) &&
                $attributes[0]->getArguments()["primary"] === true
            ) {
                return $property->getName();
            }
        }
        return null;
    }

    /**
     * Count the number of records
     */
    public function count(string $column = "*"): int
    {
        return $this->aggregate("COUNT", $column);
    }

    /**
     * Get the sum of a column
     */
    public function sum(string $column): float
    {
        return $this->aggregate("SUM", $column);
    }

    /**
     * Get the average of a column
     */
    public function avg(string $column): float
    {
        return $this->aggregate("AVG", $column);
    }

    /**
     * Get the minimum value of a column
     */
    public function min(string $column): float
    {
        return $this->aggregate("MIN", $column);
    }

    /**
     * Get the maximum value of a column
     */
    public function max(string $column): float
    {
        return $this->aggregate("MAX", $column);
    }

    /**
     * Execute an aggregate function on the database
     */
    private function aggregate(string $function, string $column): mixed
    {
        $this->select = ["$function($column) as aggregate"];

        $stmt = $this->prepareStatement();
        $stmt->execute($this->params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result["aggregate"] ?? 0 : 0;
    }

    /**
     * @template T of object
     * @param PDOStatement $statement
     * @param class-string<T> $dtoClass
     * @return T|null
     */
    private function hydrate(PDOStatement $statement, string $dtoClass): ?object
    {
        $data = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        return $this->createDtoFromData($data, $dtoClass);
    }

    /**
     * @template T of object
     * @param PDOStatement $statement
     * @param class-string<T> $dtoClass
     * @return array<T>
     */
    private function hydrateAll(
        PDOStatement $statement,
        string $dtoClass
    ): array {
        $dataArray = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (empty($dataArray)) {
            return [];
        }
        $dtos = [];
        foreach ($dataArray as $data) {
            $dtos[] = $this->createDtoFromData($data, $dtoClass);
        }
        return $dtos;
    }

    /**
     * @template T of object
     * @param array<string, mixed> $data
     * @param class-string<T> $dtoClass
     * @return T
     */
    private function createDtoFromData(array $data, string $dtoClass): object
    {
        $reflectionClass = new \ReflectionClass($dtoClass);
        $constructorParams = [];

        foreach (
            $reflectionClass->getConstructor()->getParameters()
            as $param
        ) {
            $name = $param->getName();
            if (array_key_exists($name, $data)) {
                $value = $data[$name];
                $paramType = $param->getType();

                if (
                    $paramType instanceof \ReflectionNamedType &&
                    $paramType->getName() === "DateTimeImmutable" &&
                    is_string($value)
                ) {
                    try {
                        $constructorParams[$name] = new \DateTimeImmutable(
                            $value
                        );
                    } catch (\Exception $e) {
                        $constructorParams[$name] = null;
                    }
                } else {
                    $constructorParams[$name] = $value;
                }
            } else {
                $constructorParams[$name] = null;
            }
        }
        return $reflectionClass->newInstanceArgs($constructorParams);
    }

    private function prepareStatement(): PDOStatement
    {
        $sql = "SELECT " . implode(", ", $this->select ?: ["*"]);
        $sql .= " FROM $this->table";

        // Add joins if any
        if (!empty($this->joins)) {
            $sql .= " " . implode(" ", $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(" AND ", $this->where);
        }

        // Add GROUP BY if any
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(", ", $this->groupBy);
        }

        // Add HAVING if any
        if ($this->having) {
            $sql .= " HAVING $this->having";
        }

        if ($this->orderBy) {
            $sql .= " ORDER BY $this->orderBy";
        }

        if ($this->limit) {
            $sql .= " LIMIT $this->limit";
        }

        // Add OFFSET if any
        if ($this->offset) {
            $sql .= " OFFSET $this->offset";
        }

        return $this->pdo->prepare($sql);
    }

    public function table(): string
    {
        return $this->table;
    }

    public function createTable(array $columns, array $constraints = []): self
    {
        $sql = "CREATE TABLE $this->table (";
        $columnDefinitions = [];

        foreach ($columns as $name => $definition) {
            $columnDefinitions[] = "$name $definition";
        }

        $sql .= implode(", ", $columnDefinitions);

        if (!empty($constraints)) {
            $sql .= ", " . implode(", ", $constraints);
        }

        $sql .= ")";

        $this->createTableSql = $sql;
        return $this;
    }

    public function dropTable(): string
    {
        $this->dropTableSql = "DROP TABLE IF EXISTS $this->table";
        return $this->dropTableSql;
    }

    public function getSql(): string
    {
        return $this->createTableSql ?? $this->dropTableSql ?? "";
    }
}
