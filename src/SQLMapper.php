<?php

namespace SQLMapper;

use Exception;
use PDO;
use PDOStatement;

/**
 * Class SQLMapper
 * @author Adam Łożyński
 */
class SQLMapper
{
  const EXCEPTION_TABLE_NOT_EXISTS = 'Table `%s` does not exists or does not contain a primary key column.';
  const EXCEPTION_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING = 'Can not erase - Primary key value not given.';
  const EXCEPTION_ADDING_PROBLEM = 'Problem occurred while inserting new row to table.';
  const EXCEPTION_SAVING_PROBLEM = 'Problem occurred while updating row.';
  const EXCEPTION_ERASING_PROBLEM = 'Problem occurred while erasing row.';
  const EXCEPTION_WRONG_PARAMS_AMOUNT = 'Wrong parameters amount.';
  const EXCEPTION_KEY_NOT_NUMERIC = 'Given key is not numeric';

  const SQL_MAPPER_PROPERTIES = 'SQLMapperProperties';
  const COLUMNS_KEY_COLUMN = 'Field';
  const COLUMNS_KEY_PRIMARY = 'PRI';
  
  protected $SQLMapperProperties;

  /**
   * @param $property
   * @return mixed
   */
  public function __get($property)
  {
    if (!isset($this->{$property})) {
      $this->{$property} = false;
    }
    return $this->{$property};
  }

  /**
   * SQLMapper constructor.
   * @param $connection
   * @param $table
   * @param $pk
   * @throws Exception
   */
  public function __construct(PDO $connection, string $table, $pk = null)
  {
    $this->SQLMapperProperties = new SQLMapperProperties($connection, $table);
    return $pk ? $this->loadById($pk) : true;
  }

  protected function findPrimaryKeyColumn()
  {
    $sql = strtr("SHOW COLUMNS FROM %TABLE% WHERE COLUMNS.Key = '%KEY%'", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%KEY%' => self::COLUMNS_KEY_PRIMARY
    ]);

    /** @var PDOStatement $row */
    if ($row = $this->SQLMapperProperties->Connection->query($sql)) {
      $this->SQLMapperProperties->PrimaryKeyColumn = $row->fetch(PDO::FETCH_ASSOC)[self::COLUMNS_KEY_COLUMN];
      return true;
    }
    return false;
  }

  protected function clearProperties()
  {
    foreach ($this as $key => $property) {
      if ($key !== self::SQL_MAPPER_PROPERTIES) {
        unset($this->{$key});
      }
    }
  }

  /**
   * @param array $where
   * @return array|bool
   * @throws Exception
   */
  public function find(array $where = [])
  {
    $whereQuery = $where[0] ?: ["1 = 1"];
    $whereParams = [];
    foreach($where as $key=>$param) if($key > 0) {
      $whereParams[] = $param;
    }
    if(mb_substr_count($whereQuery, '?') !== count($whereParams)) {
      throw new SQLMapperException(self::EXCEPTION_WRONG_PARAMS_AMOUNT);
    }

    $this->clearProperties();
    if (!$this->findPrimaryKeyColumn()) {
      throw new SQLMapperException(printf(self::EXCEPTION_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
    }

    $sql = strtr("SELECT * FROM %TABLE% WHERE %WHERE%", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%WHERE%' => $whereQuery
    ]);

    /** @var PDOStatement $preparedQuery */
    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    $result = [];
    if ($preparedQuery->execute($whereParams)) {
      foreach ($preparedQuery->fetchAll(PDO::FETCH_ASSOC) as $col => $value) {
        $newRow = new SQLMapper($this->SQLMapperProperties->Connection, $this->SQLMapperProperties->Table);
        foreach ($value as $key => $property) {
          $newRow->{$key} = $property;
        }
        $newRow->SQLMapperProperties->PrimaryKeyColumn = $this->SQLMapperProperties->PrimaryKeyColumn;
        $newRow->SQLMapperProperties->PrimaryKeyValue = $value[$this->SQLMapperProperties->PrimaryKeyColumn];
        $result[] = $newRow;
      }
    }
    return $result;
  }

    /**
     * @param array $where
     * @return array|bool
     * @throws Exception
     */
  public function load(array $where)
  {
    $whereQuery = $where[0];
    $whereParams = [];
    foreach($where as $key=>$param) if($key > 0) {
      $whereParams[] = $param;
    }
    if(mb_substr_count($whereQuery, '?') !== count($whereParams)) {
      throw new SQLMapperException(self::EXCEPTION_WRONG_PARAMS_AMOUNT);
    }

    $this->clearProperties();
    if (!$this->findPrimaryKeyColumn()) {
      throw new SQLMapperException(printf(self::EXCEPTION_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
    }

    $sql = strtr("SELECT * FROM %TABLE% WHERE %WHERE%", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%WHERE%' => $whereQuery
    ]);

    /** @var PDOStatement $preparedQuery */
    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    if ($preparedQuery->execute($whereParams)) {
      $fetch = $preparedQuery->fetchAll(PDO::FETCH_ASSOC);
      $fetch = reset($fetch);
      $this->clearProperties();
      if($fetch) {
        foreach ($fetch as $key => $property) {
          $this->{$key} = $property;
        }
        $this->SQLMapperProperties->PrimaryKeyValue = $fetch[$this->SQLMapperProperties->PrimaryKeyColumn];
        return true;
      }
    }
    $this->SQLMapperProperties->PrimaryKeyValue = NULL;
    return false;
  }

  /**
   * @param int|string $primary
   * @return mixed
   * @throws Exception
   */
  public function loadById($primary)
  {
    if(!is_numeric($primary)) {
      throw new SQLMapperException(self::EXCEPTION_KEY_NOT_NUMERIC);
    }
    $this->clearProperties();

    if (!$this->findPrimaryKeyColumn()) {
      throw new SQLMapperException(sprintf(self::EXCEPTION_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
    }

    $sql = strtr("SELECT * FROM %TABLE% WHERE %PK_COLUMN% = %PK_VALUE%", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn,
      '%PK_VALUE%' => $primary
    ]);

    if ($result = $this->SQLMapperProperties->Connection->query($sql)->fetch(PDO::FETCH_ASSOC)) {
      $this->SQLMapperProperties->PrimaryKeyValue = $primary;
      foreach ($result as $col => $value) {
        $this->{$col} = $value;
      }
      return true;
    }
    $this->SQLMapperProperties->PrimaryKeyValue = NULL;
    return false;
  }

  /**
   * @return bool
   * @throws Exception
   */
  public function save()
  {
    if (!$this->SQLMapperProperties->PrimaryKeyValue) {
      $newKey = isset($this->{$this->SQLMapperProperties->PrimaryKeyColumn}) && !empty($this->{$this->SQLMapperProperties->PrimaryKeyColumn}) ? $this->{$this->SQLMapperProperties->PrimaryKeyColumn} : NULL;
      return $this->add($newKey);
    }
    $set = [];
    $values = [];
    foreach ($this as $key => $property) {
      if ($key !== self::SQL_MAPPER_PROPERTIES) {
        $set[] = $this->SQLMapperProperties->Table. '.' .$key . ' = ?';
        $values[] = $property;
      }
    }

    $sql = strtr("UPDATE %TABLE% SET %SET% WHERE %PK_COLUMN% = %PK_VALUE%", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%SET%' => implode(',', $set),
      '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn,
      '%PK_VALUE%' => $this->SQLMapperProperties->PrimaryKeyValue
    ]);

    /** @var PDOStatement $preparedQuery */
    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    if (!$preparedQuery->execute($values)) {
      throw new SQLMapperException(self::EXCEPTION_SAVING_PROBLEM);
    }
    return true;
  }

  /**
   * @param int|string $newKey
   * @return bool
   * @throws Exception
   */
  public function add($newKey = NULL)
  {
    if($newKey && !is_numeric($newKey)) {
      throw new SQLMapperException(self::EXCEPTION_KEY_NOT_NUMERIC);
    }
    if (!$this->findPrimaryKeyColumn()) {
      throw new SQLMapperException(printf(self::EXCEPTION_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
    }

    $columns = [];
    $values = [];
    $QM = [];

    foreach ($this as $key => $property) {
      if ($key !== self::SQL_MAPPER_PROPERTIES) {
        $columns[] = $this->SQLMapperProperties->Table. '.' .$key;
        $values[] = $key === $this->SQLMapperProperties->PrimaryKeyColumn ? $newKey : $property;
        $QM[] = '?';
      }
    }

    $sql = strtr("INSERT INTO %TABLE% (%COLUMNS%) VALUES (%VALUES%)", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%COLUMNS%' => implode(',', $columns),
      '%VALUES%' => implode(',', $QM)
    ]);

    /** @var PDOStatement $preparedQuery */
    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    if (!$result = $preparedQuery->execute($values)) {
      throw new SQLMapperException(self::EXCEPTION_ADDING_PROBLEM);
    }
    $this->{$this->SQLMapperProperties->PrimaryKeyColumn} = $this->SQLMapperProperties->Connection->lastInsertId();
    $this->SQLMapperProperties->PrimaryKeyValue = $this->{$this->SQLMapperProperties->PrimaryKeyColumn};
    return true;
  }

  /**
   * @return bool
   * @throws Exception
   */
  public function erase()
  {
    if (!$this->SQLMapperProperties->PrimaryKeyValue) {
      throw new SQLMapperException(self::EXCEPTION_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING);
    }

    $sql = strtr("DELETE FROM %TABLE% WHERE %PK_COLUMN% = %PK_VALUE%", [
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn,
      '%PK_VALUE%' => $this->SQLMapperProperties->PrimaryKeyValue
    ]);

    if(!$this->SQLMapperProperties->Connection->query($sql)) {
      throw new SQLMapperException(self::EXCEPTION_ERASING_PROBLEM);
    }

    $this->clearProperties();
    return true;
  }

    /**
     * @return bool
     */
  public function reset()
  {
    $this->SQLMapperProperties->PrimaryKeyValue = NULL;
    $this->clearProperties();
    return true;
  }

}