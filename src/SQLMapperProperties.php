<?php

namespace SQLMapper;

use PDO;

class SQLMapperProperties
{
    public PDO $PDO;
    public string $Table;
    public ?array $Schema = NULL;
    public ?string $PrimaryKeyColumn = NULL;
    public ?string $PrimaryKeyValue = NULL;


    public function __construct(PDO $pdo, string $table, array $schema = null)
    {
        $this->PDO = $pdo;
        $this->Table = $table;
        if (!$this->Schema = $schema) {
            $database = $this->PDO->query('SELECT DATABASE()')->fetchColumn();
            if (!$query = $this->PDO->query('DESCRIBE ' . $database . '.' . $table)) {
                throw new SQLMapperException(sprintf(Consts::EXCEPTION_TABLE_NOT_EXISTS, $table));
            }
            $this->Schema = $query->fetchAll(PDO::FETCH_OBJ);
        }
        foreach ($this->Schema as $column) {
            if ($column->{Consts::SCHEMA_COLUMN_KEY} == Consts::SCHEMA_COLUMN_KEY_VALUE_PRIMARY) {
                $this->PrimaryKeyColumn = $column->{Consts::SCHEMA_COLUMN_FIELD};
            }
        }
        if (!$this->PrimaryKeyColumn) {
            throw new SQLMapperException(sprintf(Consts::EXCEPTION_TABLE_NOT_CONTAIN_PRIMARY_KEY_COLUMN, $table));
        }
    }

}
