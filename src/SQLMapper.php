<?php

namespace SQLMapper;

use PDO;

/**
 * Class SQLMapper
 * @author Adam Łożyński
 * @date 2018-10-20
 */
class SQLMapper
{
  const ENABLE_TRACING_IN_NOTICES = true;
  const STOP_APP_ON_NOTICE = true;
  const PRINT_NOTICE_ON_STOP_APP = true;
  const ADD_INSTEAD_SAVING_WHEN_PRIMARY_KEY_VALUE_NOT_SET = true;

  const NOTICE_TABLE_NOT_EXISTS = 'Table `%s` does not exists or does not contain a primary key column.';
  const NOTICE_PRIMARY_KEY_VALUE_NOT_SET_WHILE_SAVING = 'Can not save - Primary key value not given.';
  const NOTICE_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING = 'Can not erase - Primary key value not given.';
  const NOTICE_ADDING_PROBLEM = 'Problem occured while inserting new row to table.';
  const NOTICE_SAVING_PROBLEM = 'Problem occured while updating row.';
  const NOTICE_ERASING_PROBLEM = 'Problem occured while erasing row.';
  const NOTICE_WRONG_PARAMS_AMOUNT = 'Wrong parameters amount.';
  const NOTICE_KEY_NOT_NUMERIC = 'Given key is not numeric';

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
   * @param bool $pk
   */
  public function __construct($connection, $table, $pk = null)
  {
    $this->SQLMapperProperties = new \stdClass();
    $this->SQLMapperProperties->Connection = $connection;
    $this->SQLMapperProperties->Table = $table;
    $this->SQLMapperProperties->PrimaryKeyColumn = NULL;
    $this->SQLMapperProperties->PrimaryKeyValue = NULL;
    $this->SQLMapperProperties->Notices = array();

    if($pk) {
      return $this->loadById($pk);
    }
    return true;
  }

  protected function findPrimaryKeyColumn()
  {
    $sql = strtr("SHOW COLUMNS FROM %TABLE% WHERE COLUMNS.Key = '%KEY%'", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%KEY%' => SQLMapper::COLUMNS_KEY_PRIMARY
    ));

    if ($row = $this->SQLMapperProperties->Connection->query($sql)) {
      $this->SQLMapperProperties->PrimaryKeyColumn = $row->fetch(PDO::FETCH_ASSOC)[SQLMapper::COLUMNS_KEY_COLUMN];
      return true;
    }
    return false;
  }

  protected function clearProperties()
  {
    foreach ($this as $key => $property) {
      if ($key !== SQLMapper::SQL_MAPPER_PROPERTIES) {
        unset($this->{$key});
      }
    }
  }

  protected function setNotice($msg)
  {
    $notice = [];
    $notice['Message'] = $msg;
    $notice['Properties'] = [
      'Table' => $this->SQLMapperProperties->Table,
      'PrimaryKeyColumn' => $this->SQLMapperProperties->PrimaryKeyColumn,
      'PrimaryKeyValue' => $this->SQLMapperProperties->PrimaryKeyValue
    ];
    $notice['Trace'] = SQLMapper::ENABLE_TRACING_IN_NOTICES ? debug_backtrace()[1] : 'disabled';

    if (SQLMapper::STOP_APP_ON_NOTICE) {
      if (SQLMapper::PRINT_NOTICE_ON_STOP_APP) {
        die(print_r($notice));
      } else {
        die('Internal error!');
      }
    } else {
      $this->SQLMapperProperties->Notices[] = $notice;
    }
  }

  /**
   * @param int|string $primary
   * @return mixed
   */
  public function loadById($primary)
  {
    if(!is_numeric($primary)) {
      $this->setNotice(SQLMapper::NOTICE_KEY_NOT_NUMERIC);
      return false;
    }
    $this->clearProperties();

    if (!$this->findPrimaryKeyColumn()) {
      $this->setNotice(sprintf(SQLMapper::NOTICE_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
      return false;
    }

    $sql = strtr("SELECT * FROM %TABLE% WHERE %PK_COLUMN% = %PK_VALUE%", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn,
      '%PK_VALUE%' => $primary
    ));

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

  public function save()
  {
    if (!$this->SQLMapperProperties->PrimaryKeyValue) {
      if(SQLMapper::ADD_INSTEAD_SAVING_WHEN_PRIMARY_KEY_VALUE_NOT_SET) {
        $newKey = isset($this->{$this->SQLMapperProperties->PrimaryKeyColumn}) && !empty($this->{$this->SQLMapperProperties->PrimaryKeyColumn}) ? $this->{$this->SQLMapperProperties->PrimaryKeyColumn} : NULL;
        return $this->add($newKey);
      }
      else{
        $this->setNotice(SQLMapper::NOTICE_PRIMARY_KEY_VALUE_NOT_SET_WHILE_SAVING);
        return false;
      }
    }
    $set = array();
    $values = array();
    foreach ($this as $key => $property) {
      if ($key !== SQLMapper::SQL_MAPPER_PROPERTIES) {
        $set[] = $this->SQLMapperProperties->Table. '.' .$key . ' = ?';
        $values[] = $property;
      }
    }

    $sql = strtr("UPDATE %TABLE% SET %SET% WHERE %PK_COLUMN% = %PK_VALUE%", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%SET%' => implode(',', $set),
      '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn,
      '%PK_VALUE%' => $this->SQLMapperProperties->PrimaryKeyValue
    ));

    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    if (!$preparedQuery->execute($values)) {
      $this->setNotice(SQLMapper::NOTICE_SAVING_PROBLEM);
      return false;
    }
    return true;
  }

  /**
   * @param int|string $newKey
   * @return bool
   */
  public function add($newKey = NULL)
  {
    if($newKey && !is_numeric($newKey)) {
      $this->setNotice(SQLMapper::NOTICE_KEY_NOT_NUMERIC);
      return false;
    }
    if (!$this->findPrimaryKeyColumn()) {
      $this->setNotice(printf(SQLMapper::NOTICE_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
      return false;
    }

    $columns = array();
    $values = array();
    $QM = array();

    foreach ($this as $key => $property) {
      if ($key !== SQLMapper::SQL_MAPPER_PROPERTIES) {
        $columns[] = $this->SQLMapperProperties->Table. '.' .$key;
        $values[] = $key === $this->SQLMapperProperties->PrimaryKeyColumn ? $newKey : $property;
        $QM[] = '?';
      }
    }

    $sql = strtr("INSERT INTO %TABLE% (%COLUMNS%) VALUES (%VALUES%)", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%COLUMNS%' => implode(',', $columns),
      '%VALUES%' => implode(',', $QM)
    ));

    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    if (!$result = $preparedQuery->execute($values)) {
      $this->setNotice(SQLMapper::NOTICE_ADDING_PROBLEM);
      return false;
    }
    $this->{$this->SQLMapperProperties->PrimaryKeyColumn} = $this->SQLMapperProperties->Connection->lastInsertId();
    $this->SQLMapperProperties->PrimaryKeyValue = $this->{$this->SQLMapperProperties->PrimaryKeyColumn};
    return true;
  }

  public function erase()
  {
    if (!$this->SQLMapperProperties->PrimaryKeyValue) {
      $this->setNotice(SQLMapper::NOTICE_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING);
      return false;
    }

    $sql = strtr("DELETE FROM %TABLE% WHERE %PK_COLUMN% = %PK_VALUE%", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%PK_COLUMN%' => $this->SQLMapperProperties->PrimaryKeyColumn,
      '%PK_VALUE%' => $this->SQLMapperProperties->PrimaryKeyValue
    ));

    if(!$this->SQLMapperProperties->Connection->query($sql)) {
      $this->setNotice(SQLMapper::NOTICE_ERASING_PROBLEM);
      return false;
    }

    $this->clearProperties();
    return true;
  }

  /**
   * @param array $where
   * @return array|bool
   */
  public function find($where = array())
  {
    $where = empty($where) ? array("1 = 1") : $where;
    $whereQuery = $where[0];
    $whereParams = array();
    foreach($where as $key=>$param) {
      $key > 0 ? $whereParams[] = $param : null;
    }
    if(mb_substr_count($whereQuery, '?') !== count($whereParams)) {
      $this->setNotice(SQLMapper::NOTICE_WRONG_PARAMS_AMOUNT);
      return array();
    }

    $this->clearProperties();
    if (!$this->findPrimaryKeyColumn()) {
      $this->setNotice(printf(SQLMapper::NOTICE_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
      return false;
    }

    $sql = strtr("SELECT * FROM %TABLE% WHERE %WHERE%", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%WHERE%' => $whereQuery
    ));

    $preparedQuery = $this->SQLMapperProperties->Connection->prepare($sql);
    $result = array();
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
   */
  public function load($where = array())
  {
    $where = empty($where) ? array("1 = 1") : $where;
    $whereQuery = $where[0];
    $whereParams = array();
    foreach($where as $key=>$param) {
      $key > 0 ? $whereParams[] = $param : null;
    }
    if(mb_substr_count($whereQuery, '?') !== count($whereParams)) {
      $this->setNotice(SQLMapper::NOTICE_WRONG_PARAMS_AMOUNT);
      return array();
    }

    $this->clearProperties();
    if (!$this->findPrimaryKeyColumn()) {
      $this->setNotice(printf(SQLMapper::NOTICE_TABLE_NOT_EXISTS, $this->SQLMapperProperties->Table));
      return false;
    }

    $sql = strtr("SELECT * FROM %TABLE% WHERE %WHERE%", array(
      '%TABLE%' => $this->SQLMapperProperties->Table,
      '%WHERE%' => $whereQuery
    ));

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

  public function getNotices()
  {
    return $this->SQLMapperProperties->Notices;
  }

}