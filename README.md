# SQL Mapper
PHP library to manage MySQL tables in active record pattern.

## Installation
```bash
composer require lozynskiadam/sqlmapper
```

## Requirements
* PHP 7.0.1+
* MySQL database
* PDO extension

## Introduction


#### Usage
SQL Mapper provides several methods that allows you to pass arguments into it to perform queries on your database without writing raw SQL.
```bash
find ( array $where ) : array
load ( array $where ) : object|false
save ( ) : bool
add ( [ int $primaryKey ] ) : bool
erase ( ) : bool
reset ( ) : bool
```

#### Example

```php
require_once '../vendor/autoload.php';

$PDO = new PDO("mysql:host=localhost;dbname=test", 'root, '');

// get table
$table = new \SQLMapper\SQLMapper($PDO, 'product');

// add new record
$table->Name = 'Apple';
$table->Price = '0.5';
$table->add();

// update records by column
$name = 'Apple';
$newPrice = 0.99;
foreach($table->find(array('Name = ?', $name)) as $record) {
  $record->Price = $newPrice;
  $record->save();
}

// erase record by column
$table->load(array('Id = 5');
$table->erase();
```