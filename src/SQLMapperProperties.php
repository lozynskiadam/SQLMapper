<?php

namespace SQLMapper;

use PDO;

class SQLMapperProperties
{
    public $Connection;
    public $Table;
    public $PrimaryKeyColumn = NULL;
    public $PrimaryKeyValue = NULL;

    public function __construct(PDO $connection, string $table)
    {
        $this->Connection = $connection;
        $this->Table = $table;
    }
}