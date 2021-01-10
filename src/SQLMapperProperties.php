<?php

namespace SQLMapper;

use PDO;

class SQLMapperProperties
{
    public $PDO;
    public $Table;
    public $Schema = [];
    public $PrimaryKeyColumn = NULL;
    public $PrimaryKeyValue = NULL;

    public function __construct(PDO $pdo, string $table, array $schema = null)
    {
        $this->PDO = $pdo;
        $this->Table = $table;
        if(!($this->Schema = $schema)) {
            $database = $this->PDO->query('SELECT DATABASE()')->fetchColumn();
            if(!$query = $this->PDO->query('DESCRIBE ' .$database. '.' .$table)) {
                throw new SQLMapperException(printf(Consts::EXCEPTION_TABLE_NOT_EXISTS, $table));
            }
            $this->Schema = $query->fetchAll(PDO::FETCH_OBJ);
        }
        foreach($this->Schema as $column) {
            if($column->{Consts::SCHEMA_COLUMN_KEY} == Consts::SCHEMA_COLUMN_KEY_VALUE_PRIMARY) {
                $this->PrimaryKeyColumn = $column->Field;
            }
        }
        if(!$this->PrimaryKeyColumn) {
            throw new SQLMapperException(printf(Consts::EXCEPTION_TABLE_NOT_CONTAIN_PRIMARY_KEY_COLUMN, $table));
        }
    }
}