#Installation
```bash
composer require lozynskiadam/sqlmapper
```

#Introduction

#####Connecting to the database

If you don't have a PDO connection yet it can be established using **Connector** class:
```php
$PDO = new \SQLMapper\Connector($server, $user, $pass, $db);
$conn = $PDO->getConnection();
```

#####SQLMapper methods:

```bash
find ( array $where ) : array
load ( array $where ) : object|false
save ( ) : bool
add ( [ int $key ] ) : bool
erase ( ) : bool;
```

#####Example:

```php
require_once '../vendor/autoload.php';

$PDO = new \SQLMapper\Connector('localhost', 'root', '', $db);

// get table
$table = new \SQLMapper\SQLMapper($PDO->getConnection(), 'product');

// add new record
$table->Name = 'Apple';
$table->Price = '0.5';
$table->add();

// erase record where 'Id' = 5
$table->load(array('Id = 5');
$table->erase();

// update records by name
$name = 'Apple';
$newPrice = 0.99;
foreach($table->find(array('Name = ?', $name)) as $record) {
  $record->Price = $newPrice;
  $record->save();
}
```