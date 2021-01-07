<?php

class SQLMapperTest extends TestCase
{
    /** @var \SQLMapper\SQLMapper */
    public $Table;

    public $name = 'SQLMapperTestTable';
    public $db = 'test';
    public $user = 'root';
    public $password = '';

    public function setUpBeforeClass()
    {
        $this->PDO = new PDO("mysql:host=localhost;dbname=$this->db", $this->user, $this->password);
        $this->PDO->exec("DROP TABLE IF EXISTS `$this->name`;");
        $this->PDO->exec("CREATE TABLE `$this->name` (`Id` INT NOT NULL AUTO_INCREMENT, `Name` VARCHAR(10) NULL, `Price` DECIMAL(8,2) NULL, PRIMARY KEY (`Id`));");
        $this->PDO->exec("INSERT INTO `$this->name` (`Id`, `Name`, `Price`) VALUES ('1', 'First', '100.00')");
        $this->PDO->exec("INSERT INTO `$this->name` (`Id`, `Name`, `Price`) VALUES ('2', 'Second', '16.00')");
        $this->PDO->exec("INSERT INTO `$this->name` (`Id`, `Name`, `Price`) VALUES ('3', 'Third', '140.40')");
        $this->PDO->exec("INSERT INTO `$this->name` (`Id`, `Name`, `Price`) VALUES ('4', 'Fourth', '44.10')");
        $this->PDO->exec("INSERT INTO `$this->name` (`Id`, `Name`) VALUES ('5', 'Fifth')");
        $this->Table = new SQLMapper\SQLMapper($this->PDO, $this->name);
    }

    public function tearDownAfterClass()
    {
        $this->PDO->exec("DROP TABLE IF EXISTS `$this->name`;");
    }

    public function testLoad()
    {
        $this->Table->load(['Name = ?', 'First']);
        $this->assertEqual($this->Table->Id, '1');

        $this->Table->load(['Id > 1']);
        $this->assertEqual($this->Table->Id, '2');

        $this->Table->load(['Price IS NULL']);
        $this->assertEqual($this->Table->Id, '5');

        $this->Table->load(['Price IS NOT NULL']);
        $this->assertEqual($this->Table->Id, '1');

        $this->Table->load(['Name = ?', 'NotExistingName']);
        $this->assertEqual($this->Table->Id, null);
    }

    public function testFind()
    {
        $getIds = function ($obj) {
            return $obj->Id;
        };

        $ids = array_map($getIds, $this->Table->find());
        $this->assertEqual($ids, ['1','2','3','4','5']);

        $ids = array_map($getIds, $this->Table->find([]));
        $this->assertEqual($ids, ['1','2','3','4','5']);

        $ids = array_map($getIds, $this->Table->find(['Name = ?', 'First']));
        $this->assertEqual($ids, ['1']);

        $ids = array_map($getIds, $this->Table->find(['Id = ? OR Name = ?', '1', 'Second']));
        $this->assertEqual($ids, ['1', '2']);

        $ids = array_map($getIds, $this->Table->find(['Id = ? AND Name = ?', '1', 'Second']));
        $this->assertEqual($ids, []);

        $ids = array_map($getIds, $this->Table->find(['Price > 50']));
        $this->assertEqual($ids, ['1', '3']);

        $ids = array_map($getIds, $this->Table->find(['Price > ?', 50]));
        $this->assertEqual($ids, ['1', '3']);

        $ids = array_map($getIds, $this->Table->find(['Price IS NOT NULL']));
        $this->assertEqual($ids, ['1', '2', '3', '4']);

        $ids = array_map($getIds, $this->Table->find(['Price IS NULL']));
        $this->assertEqual($ids, ['5']);

        $this->expectException($this->Table->find, ['Id = ? AND Name = ?', '1']);
    }

    public function testSave()
    {
        $this->PDO->beginTransaction();

        $this->Table->load(['Id = ?', 1]);
        $this->Table->Name = 'Updated';
        $this->Table->save();
        $this->assertEqual($this->Table->Name, 'Updated');

        $this->Table->load(['Id = ?', 1]);
        $this->assertEqual($this->Table->Name, 'Updated');

        $this->Table->Name = 'AAAAAAAAAABBBBB'; // exceed column length
        $this->Table->save();
        $this->assertEqual($this->Table->Name, 'AAAAAAAAAABBBBB');

        $this->Table->load(['Id = ?', 1]);
        $this->assertEqual($this->Table->Name, 'AAAAAAAAAA');

        $this->Table->Id = '10';
        $this->Table->save();
        $this->assertEqual($this->Table->Id, '10');

        $this->Table->load(['Id = ?', 1]);
        $this->assertEqual($this->Table->Id, null);

        $this->Table->load(['Id = ?', 10]);
        $this->assertEqual($this->Table->Id, '10');

        foreach($this->Table->find() as $row) {
            $row->Price = null;
            $row->save();
        }
        foreach($this->Table->find() as $row) {
            $this->assertEqual($row->Price, null);
        }

        $this->Table->Id = 2;
        $this->expectException($this->Table->save);

        $this->PDO->rollBack();
    }

    public function testAdd()
    {
        $this->PDO->beginTransaction();

        $this->Table->reset();
        $this->Table->Name = '6th';
        $this->Table->add();
        $this->assertGreater($this->Table->Id, 5);

        $this->Table->reset();
        $this->Table->Name = '7th';
        $this->Table->add(7);
        $this->assertEqual($this->Table->Id, '7');

        $this->Table->load(['Id = ?', '1']);
        $this->Table->Name = '8th';
        $this->Table->add();
        $this->assertGreater($this->Table->Id, 5);

        $this->Table->Name = '7th';
        $this->expectException($this->Table->add, '1');

        $this->PDO->rollBack();
    }

    public function testErase()
    {
        $this->PDO->beginTransaction();

        $this->Table->load(['Id = ?', 1]);
        $this->Table->erase();
        $this->assertEqual($this->Table->Id, null);

        $this->Table->load(['Id = ?', 1]);
        $this->assertEqual($this->Table->Id, null);

        $this->PDO->rollBack();
    }

    public function testReset()
    {
        $this->Table->load(['Id = ?', 1]);
        $this->Table->reset();
        $this->assertEqual($this->Table->Id, null);
        $this->assertEqual($this->Table->Name, null);
    }

}