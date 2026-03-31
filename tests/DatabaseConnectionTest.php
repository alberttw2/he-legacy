<?php

use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function testFactoryReturnsPDO()
    {
        $pdo = PDO_DB::factory();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testFactoryReturnsSameInstance()
    {
        $pdo1 = PDO_DB::factory();
        $pdo2 = PDO_DB::factory();
        $this->assertSame($pdo1, $pdo2);
    }

    public function testCanQueryDatabase()
    {
        $pdo = PDO_DB::factory();
        $result = $pdo->query("SELECT 1 as val")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $result['val']);
    }

    public function testGameTablesExist()
    {
        $pdo = PDO_DB::factory();
        $tables = ['users', 'npc', 'software', 'round', 'missions', 'hardware'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'game' AND table_name = '$table'");
            $this->assertEquals(1, $stmt->fetchColumn(), "Table '$table' should exist");
        }
    }
}
