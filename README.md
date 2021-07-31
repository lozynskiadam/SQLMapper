# SQL Mapper
Tiny PHP library to manage MySQL tables in active record pattern.

## Installation
```bash
composer require lozynskiadam/sqlmapper
```

## Requirements
* PHP >= 7.4
* MySQL database
* PDO extension

## Introduction


#### Usage
SQL Mapper provides several methods that allows you to pass arguments into it to perform queries on your database without writing raw SQL.
```bash
load ( array $where ) : bool
find ( [ array $where ] ) : array
save ( ) : bool
add ( [ int $primaryKey ] ) : bool
erase ( ) : bool
reset ( ) : void
```

#### Example

```php
require_once '../vendor/autoload.php';

$PDO = new PDO("mysql:host=localhost;dbname=test", 'root', '');

// fetch table "products"
$table = new \SQLMapper\SQLMapper($PDO, 'products');

// add new record
$table->Name = 'Apple';
$table->Price = '0.5';
$table->add();

// update records by column
$newPrice = 0.99;
foreach($table->find(['Name = ?', 'Apple']) as $record) {
  $record->Price = $newPrice;
  $record->save();
}

// erase record by columns
$table->load(['Name = ? AND Price = ?', 'Apple', '0.99']);
$table->erase();
```