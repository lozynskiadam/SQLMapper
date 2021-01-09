<?php

namespace SQLMapper;

use Exception;
use PDO;
use PDOStatement;

abstract class SQLMapperCore
{
    public $SQLMapperProperties;

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if (!isset($this->{$property})) {
            $this->{$property} = null;
        }
        return $this->{$property};
    }

    /**
     * @param PDO $pdo
     * @param string $table
     * @param array|null $schema
     * @throws SQLMapperException
     */
    public function __construct(PDO $pdo, string $table, array $schema = null)
    {
        $this->SQLMapperProperties = new SQLMapperProperties($pdo, $table, $schema);
        $this->SQLMapperProperties->PrimaryKeyColumn = $this->getPrimaryKeyColumn();
        return true;
    }

    protected function getPrimaryKeyColumn()
    {
        $sql = strtr("SHOW COLUMNS FROM %TABLE% WHERE COLUMNS.Key = '%KEY%'", [
          '%TABLE%' => $this->SQLMapperProperties->Table,
          '%KEY%' => Consts::COLUMNS_KEY_PRIMARY
        ]);

        /** @var PDOStatement $row */
        if ($row = $this->SQLMapperProperties->PDO->query($sql)) {
            return $row->fetch(PDO::FETCH_ASSOC)[Consts::COLUMNS_KEY_COLUMN];
        }
        throw new SQLMapperException(printf(Consts::EXCEPTION_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
    }

    protected function clearProperties()
    {
        foreach ($this as $key => $property) {
            if ($key !== Consts::SQL_MAPPER_PROPERTIES) {
                unset($this->{$key});
            }
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