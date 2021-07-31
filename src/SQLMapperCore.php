<?php

namespace SQLMapper;

use PDO;
use PDOStatement;

abstract class SQLMapperCore
{
    protected SQLMapperProperties $SQLMapperProperties;


    public function __construct(PDO $pdo, string $table, array $schema = null)
    {
        $this->SQLMapperProperties = new SQLMapperProperties($pdo, $table, $schema);
        $this->resetProperties();
    }

    public function __get($property)
    {
        if (!$this->getColumnSchema($property)) {
            throw new SQLMapperException(sprintf(Consts::EXCEPTION_COLUMN_IN_TABLE_NOT_EXISTS, $property, $this->SQLMapperProperties->Table));
        }

        return $this->{$property};
    }

    public function __set($property, $value)
    {
        if (!$this->getColumnSchema($property)) {
            throw new SQLMapperException(sprintf(Consts::EXCEPTION_COLUMN_IN_TABLE_NOT_EXISTS, $property, $this->SQLMapperProperties->Table));
        }
        $this->{$property} = $value;
    }


    protected function resetProperties(): void
    {
        foreach ($this->SQLMapperProperties->Schema as $column) {
            $this->{$column->{Consts::SCHEMA_COLUMN_FIELD}} = null;
        }
    }

    protected function getColumnSchema(string $field): ?object
    {
        foreach ($this->SQLMapperProperties->Schema as $column) {
            if ($column->{Consts::SCHEMA_COLUMN_FIELD} === $field) {
                return $column;
            }
        }

        return null;
    }

    protected function execSQL(string $sql, array $params = []): bool
    {
        /** @var PDOStatement $preparedQuery */
        $preparedQuery = $this->SQLMapperProperties->PDO->prepare($sql);

        return $preparedQuery->execute($params);
    }

    protected function openSQL(string $sql, array $params = [], int $fetch = PDO::FETCH_ASSOC): ?array
    {
        /** @var PDOStatement $preparedQuery */
        $preparedQuery = $this->SQLMapperProperties->PDO->prepare($sql);
        if ($preparedQuery->execute($params)) {
            return $preparedQuery->fetchAll($fetch);
        }

        throw new SQLMapperException(Consts::EXCEPTION_EXECUTING_PROBLEM);
    }

}
