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
            $this->Schema = $this->PDO->query('DESCRIBE ' .$database. '.' .$table)->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}