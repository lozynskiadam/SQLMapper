<?php

namespace SQLMapper;

use PDO;
use PDOStatement;

abstract class SQLMapperCore
{
    protected $SQLMapperProperties;

    /**
     * @param PDO $pdo
     * @param string $table
     * @param array|null $schema
     * @throws SQLMapperException
     */
    public function __construct(PDO $pdo, string $table, array $schema = null)
    {
        $this->SQLMapperProperties = new SQLMapperProperties($pdo, $table, $schema);
        $this->resetProperties();
        return true;
    }

    protected function resetProperties()
    {
        foreach($this->SQLMapperProperties->Schema as $column) {
            $this->{$column->{Consts::SCHEMA_COLUMN_FIELD}} = null;
        }
    }

    protected function execSQL($sql, $params = [])
    {
        /** @var PDOStatement $preparedQuery */
        $preparedQuery = $this->SQLMapperProperties->PDO->prepare($sql);
        return $preparedQuery->execute($params);
    }

    protected function openSQL($sql, $params = [], $fetch = PDO::FETCH_ASSOC)
    {
        /** @var PDOStatement $preparedQuery */
        $preparedQuery = $this->SQLMapperProperties->PDO->prepare($sql);
        if ($preparedQuery->execute($params)) {
            return $preparedQuery->fetchAll($fetch);
        }
        return false;
    }
}