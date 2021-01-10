<?php

use PHPUnit\Framework\TestCase;
use SQLMapper\SQLMapper;
use SQLMapper\SQLMapperException;

class SQLMapperTest extends TestCase
{
    /** @var PDO */
    protected static $PDO;

    /** @var SQLMapper */
    protected static $SQLMapper;

    protected static $name = 'SQLMapperTestTable';
    protected static $db = 'test';
    protected static $user = 'root';
    protected static $password = '';

    public static function setUpBeforeClass() : void
    {
        self::$PDO = new PDO("mysql:host=localhost;dbname=" .self::$db, self::$user, self::$password);
        self::$PDO->exec("DROP TABLE IF EXISTS `" .self::$name. "`;");
        self::$PDO->exec("CREATE TABLE `" .self::$name. "` (`Id` INT NOT NULL AUTO_INCREMENT, `Name` VARCHAR(10) NULL, `Price` DECIMAL(8,2) NULL, PRIMARY KEY (`Id`));");
        self::$PDO->exec("INSERT INTO `" .self::$name. "` (`Id`, `Name`, `Price`) VALUES ('1', 'First', '100.00')");
        self::$PDO->exec("INSERT INTO `" .self::$name. "` (`Id`, `Name`, `Price`) VALUES ('2', 'Second', '16.00')");
        self::$PDO->exec("INSERT INTO `" .self::$name. "` (`Id`, `Name`, `Price`) VALUES ('3', 'Third', '140.40')");
        self::$PDO->exec("INSERT INTO `" .self::$name. "` (`Id`, `Name`, `Price`) VALUES ('4', 'Fourth', '44.10')");
        self::$PDO->exec("INSERT INTO `" .self::$name. "` (`Id`, `Name`) VALUES ('5', 'Fifth')");
        self::$SQLMapper = new SQLMapper(self::$PDO, self::$name);
    }

    public static function tearDownAfterClass() : void
    {
        self::$PDO->exec("DROP TABLE IF EXISTS `" .self::$name. "`;");
        self::$PDO = null;
    }

    public function testConstruct_WhenPassingTableThatNotExists()
    {
        $this->expectException(SQLMapperException::class);
        new SQLMapper(self::$PDO, 'not_existing_table');
    }

    public function testGetter_WhenGettingPropertyThatDoesNotMatchAnyColumn()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->NotExistingColumn;
    }

    public function testSetter_WhenSettingPropertyThatDoesNotMatchAnyColumn()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->NotExistingColumn = '1';
    }

    public function testLoad_WithQueryVariations()
    {
        self::$SQLMapper->load(['Price IS NULL']);
        $this->assertSame('5', self::$SQLMapper->Id);

        self::$SQLMapper->load(['Price IS NOT NULL']);
        $this->assertSame('1', self::$SQLMapper->Id);

        self::$SQLMapper->load(['Id IS NULL']);
        $this->assertSame(null, self::$SQLMapper->Id);

        self::$SQLMapper->load(['Id > 1']);
        $this->assertSame('2', self::$SQLMapper->Id);
    }

    public function testLoad_WhenQueryIsNotDetermined()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->load([]);
    }

    public function testLoad_WithBindedParameter()
    {
        self::$SQLMapper->load(['Name = ?', 'First']);
        $this->assertSame('1', self::$SQLMapper->Id);

        self::$SQLMapper->load(['Id = ? AND Name = ?', '1', 'First']);
        $this->assertSame('1', self::$SQLMapper->Id);

        self::$SQLMapper->load(['Name = ?', 'Last']);
        $this->assertSame(null, self::$SQLMapper->Id);

        self::$SQLMapper->load(['Id = ? OR Name = ?', '100', 'Second']);
        $this->assertSame('2', self::$SQLMapper->Id);
    }

    public function testLoad_WhenBindedParametersAmountIsGreaterThanPlaceholdersAmount()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->load(['Name = ?', '1', '2']);
    }

    public function testLoad_WhenBindedParametersAmountIsLowerThanPlaceholdersAmount()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->load(['Name = ? OR Name = ?', '1']);
    }

    public function testFind_WithQueryVariations()
    {
        $getIds = function($arg) {
            return array_map(function ($obj) { return $obj->Id; }, $arg);
        };

        $ids = $getIds(self::$SQLMapper->find());
        $this->assertSame(['1','2','3','4','5'], $ids);

        $ids = $getIds(self::$SQLMapper->find([]));
        $this->assertSame(['1','2','3','4','5'], $ids);

        $ids = $getIds(self::$SQLMapper->find(['Price > 50']));
        $this->assertSame(['1', '3'], $ids);

        $ids = $getIds(self::$SQLMapper->find(['Price IS NOT NULL']));
        $this->assertSame(['1', '2', '3', '4'], $ids);

        $ids = $getIds(self::$SQLMapper->find(['Price IS NULL']));
        $this->assertSame(['5'], $ids);
    }

    public function testFind_WithBindedParameter()
    {
        $getIds = function($arg) {
            return array_map(function ($obj) { return $obj->Id; }, $arg);
        };

        $ids = $getIds(self::$SQLMapper->find(['Name = ?', 'First']));
        $this->assertSame(['1'], $ids);

        $ids = $getIds(self::$SQLMapper->find(['Id = ? OR Name = ?', '1', 'Second']));
        $this->assertSame(['1', '2'], $ids);

        $ids = $getIds(self::$SQLMapper->find(['Id = ? AND Name = ?', '1', 'Second']));
        $this->assertSame([], $ids);

        $ids = $getIds(self::$SQLMapper->find(['Price > ?', 50]));
        $this->assertSame(['1', '3'], $ids);
    }

    public function testFind_WhenBindedParametersAmountIsGreaterThanPlaceholdersAmount()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->find(['Id = ?', '1', '2']);
    }

    public function testFind_WhenBindedParametersAmountIsLowerThanPlaceholdersAmount()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->find(['Id = ? AND Name = ?', '1']);
    }

    public function testSave()
    {
        self::$PDO->beginTransaction();

        self::$SQLMapper->load(['Id = ?', 1]);
        self::$SQLMapper->Name = 'Updated';
        self::$SQLMapper->save();
        $this->assertSame('Updated', self::$SQLMapper->Name);

        self::$SQLMapper->load(['Id = ?', 1]);
        $this->assertSame('Updated', self::$SQLMapper->Name);

        self::$PDO->rollBack();
    }

    public function testSave_WhenIteratingOverResult()
    {
        self::$PDO->beginTransaction();

        foreach(self::$SQLMapper->find() as $row) {
            $row->Price = null;
            $row->save();
        }
        foreach(self::$SQLMapper->find() as $row) {
            $this->assertSame(null, $row->Price);
        }

        self::$PDO->rollBack();
    }

    public function testSave_WhenLengthIsExceeded()
    {
        self::$PDO->beginTransaction();

        self::$SQLMapper->load(['Id = ?', 1]);
        self::$SQLMapper->Name = 'AAAAAAAAAABBBBB'; // exceed column length
        self::$SQLMapper->save();
        $this->assertSame('AAAAAAAAAABBBBB', self::$SQLMapper->Name);

        self::$SQLMapper->load(['Id = ?', 1]);
        $this->assertSame('AAAAAAAAAA', self::$SQLMapper->Name);

        self::$PDO->rollBack();
    }

    public function testSave_WhenPrimaryKeyAlreadyExists()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->reset();
        self::$SQLMapper->Id = 2;
        self::$SQLMapper->save();
    }

    public function testAdd_WhenAddingWithoutPrimaryKey()
    {
        self::$PDO->beginTransaction();

        self::$SQLMapper->reset();
        self::$SQLMapper->Name = '6th';
        self::$SQLMapper->add();
        $this->assertGreaterThan(5, self::$SQLMapper->Id);

        self::$PDO->rollBack();
    }

    public function testAdd_WhenAddingWithPrimaryKey()
    {
        self::$PDO->beginTransaction();

        self::$SQLMapper->load(['Id = ?', '1']);
        self::$SQLMapper->Name = '8th';
        self::$SQLMapper->add();
        $this->assertGreaterThan(5, self::$SQLMapper->Id);

        self::$PDO->rollBack();
    }

    public function testAdd_WhenPrimaryKeyAlreadyExistsException()
    {
        $this->expectException(SQLMapperException::class);
        self::$SQLMapper->Name = '7th';
        self::$SQLMapper->add('1');
    }

    public function testErase()
    {
        self::$PDO->beginTransaction();

        self::$SQLMapper->load(['Id = ?', 1]);
        self::$SQLMapper->erase();
        $this->assertSame(null, self::$SQLMapper->Id);

        self::$SQLMapper->load(['Id = ?', 1]);
        $this->assertSame(null, self::$SQLMapper->Id);

        self::$PDO->rollBack();
    }

    public function testReset()
    {
        self::$SQLMapper->load(['Id = ?', 1]);
        $this->assertSame('1', self::$SQLMapper->Id);

        self::$SQLMapper->reset();
        $this->assertSame(null, self::$SQLMapper->Id);
        $this->assertSame(null, self::$SQLMapper->Name);
    }

}
