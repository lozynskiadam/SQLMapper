<?php

namespace SQLMapper;

/**
 * Class SQLMapper
 * @author Adam Łożyński
 */
class SQLMapper extends SQLMapperCore
{
    /**
     * @param array $where
     * @return bool
     * @throws SQLMapperException
     */
    public function load(array $where)
    {
        $this->clearProperties();

        $sql = strtr("SELECT * FROM %TABLE% WHERE %WHERE%", [
          '%TABLE%' => $this->SQLMapperProperties->Table,
          '%WHERE%' => $where[0]
        ]);
        $params = [];
        foreach ($where as $key => $param) if ($key > 0) {
            $params[] = $param;
        }
        if (mb_substr_count($where[0], '?') !== count($params)) {
            throw new SQLMapperException(Consts::EXCEPTION_WRONG_PARAMS_AMOUNT);
        }

        if ($row = ($this->openSQL($sql, $params)[0] ?? false)) {
            foreach ($row as $key => $property) {
                $this->{$key} = $property;
            }
            $this->SQLMapperProperties->PrimaryKeyValue = $row[$this->SQLMapperProperties->PrimaryKeyColumn];
            return true;
        }
        $this->SQLMapperProperties->PrimaryKeyValue = null;
        return false;
    }

    /**
     * @param array $where
     * @return array
     * @throws SQLMapperException
     */
    public function find(array $where = [])
    {
        $this->clearProperties();

        $whereQuery = $where[0] ?? "1 = 1";
        $sql = strtr("SELECT * FROM %TABLE% WHERE %WHERE%", [
          '%TABLE%' => $this->SQLMapperProperties->Table,
          '%WHERE%' => $whereQuery
        ]);
        $params = [];
        foreach ($where as $key => $param) if ($key > 0) {
            $params[] = $param;
        }
        if (mb_substr_count($whereQuery, '?') !== count($params)) {
            throw new SQLMapperException(Consts::EXCEPTION_WRONG_PARAMS_AMOUNT);
        }

        $result = [];
        foreach ($this->openSQL($sql, $params) as $row) {
            $instance = new SQLMapper($this->SQLMapperProperties->Connection, $this->SQLMapperProperties->Table);
            foreach ($row as $key => $property) {
                $instance->{$key} = $property;
            }
            $instance->SQLMapperProperties->PrimaryKeyColumn = $this->SQLMapperProperties->PrimaryKeyColumn;
            $instance->SQLMapperProperties->PrimaryKeyValue = $row[$this->SQLMapperProperties->PrimaryKeyColumn];
            $result[] = $instance;
        }
        return $result;
    }

    /**
     * @return bool
     * @throws SQLMapperException
     */
    public function save()
    {
        if (!$this->SQLMapperProperties->PrimaryKeyValue) {
            $primaryKey = $this->{$this->SQLMapperProperties->PrimaryKeyColumn} ?: null;
            return $this->add($primaryKey);
        }

        $set = [];
        $params = [];
        foreach ($this as $key => $property) {
            if ($key === Consts::SQL_MAPPER_PROPERTIES) {
                continue;
            }
            $set[] = $this->SQLMapperProperties->Table . '.' . $key . ' = ?';
            $params[] = $property;
        }

        $sql = strtr("UPDATE %TABLE% SET %SET% WHERE %PK_COLUMN% = ?", [
          '%TABLE%' => $this->SQLMapperProperties->Table,
          '%SET%' => implode(', ', $set),
          '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn
        ]);
        $params[] = $this->SQLMapperProperties->PrimaryKeyValue;

        if (!$this->execSQL($sql, $params)) {
            throw new SQLMapperException(Consts::EXCEPTION_SAVING_PROBLEM);
        }
        return true;
    }

    /**
     * @param int|string $primaryKey
     * @return bool
     * @throws SQLMapperException
     */
    public function add($primaryKey = null)
    {
        if ($primaryKey) {
            $this->{$this->SQLMapperProperties->PrimaryKeyColumn} = $primaryKey;
        }

        $columns = [];
        $params = [];
        $QM = [];

        foreach ($this as $key => $property) {
            if ($key === Consts::SQL_MAPPER_PROPERTIES) {
                continue;
            }
            $columns[] = $this->SQLMapperProperties->Table . '.' . $key;
            $params[] = $key === $this->SQLMapperProperties->PrimaryKeyColumn ? $primaryKey : $property;
            $QM[] = '?';
        }

        $sql = strtr("INSERT INTO %TABLE% (%COLUMNS%) VALUES (%VALUES%)", [
          '%TABLE%' => $this->SQLMapperProperties->Table,
          '%COLUMNS%' => implode(',', $columns),
          '%VALUES%' => implode(',', $QM)
        ]);

        if (!$this->execSQL($sql, $params)) {
            throw new SQLMapperException(Consts::EXCEPTION_ADDING_PROBLEM);
        }

        return $this->load([$this->SQLMapperProperties->PrimaryKeyColumn . ' = ?', $this->SQLMapperProperties->Connection->lastInsertId()]);
    }

    /**
     * @return bool
     * @throws SQLMapperException
     */
    public function erase()
    {
        if (!$this->SQLMapperProperties->PrimaryKeyValue) {
            throw new SQLMapperException(Consts::EXCEPTION_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING);
        }

        $sql = strtr("DELETE FROM %TABLE% WHERE %PK_COLUMN% = ?", [
          '%TABLE%' => $this->SQLMapperProperties->Table,
          '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn
        ]);
        $params = [$this->SQLMapperProperties->PrimaryKeyValue];

        if (!$this->execSQL($sql, $params)) {
            throw new SQLMapperException(Consts::EXCEPTION_ERASING_PROBLEM);
        }

        $this->clearProperties();
        return true;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->SQLMapperProperties->PrimaryKeyValue = null;
        $this->clearProperties();
    }

}