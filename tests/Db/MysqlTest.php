<?php

namespace Framelix\Framelix\Tests\Db;

use Framelix\Framelix\Db\Mysql;
use PHPUnit\Framework\TestCase;

final class MysqlTest extends TestCase
{
    /**
     * Drop dev tables after execution
     */
    public static function tearDownAfterClass(): void
    {
        Mysql::get('test')->query("DROP TABLE IF EXISTS `dev`");
    }

    public function testMisc(): void
    {
        $db = Mysql::get('test');

        // create dev table
        $this->assertTrue(
            $db->query("CREATE TABLE `dev` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `text` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (`id`) USING BTREE
            )
            COLLATE='utf8mb4_unicode_ci'
            ENGINE=InnoDB")
        );

        // insert dev text
        $testText = "foobar\"quote\"";
        $testText2 = "foobar\"quote\"2";
        $this->assertTrue(
            $db->insert("dev", ['text' => $testText])
        );

        // check different select fetch formats
        $this->assertEquals($testText, $db->fetchOne("SELECT text FROM dev"));
        $this->assertEquals([$testText], $db->fetchColumn("SELECT text FROM dev"));
        $this->assertEquals([1 => ["id" => 1, "text" => $testText]], $db->fetchAssoc("SELECT * FROM dev", null, "id"));
        $this->assertEquals([["text" => $testText]], $db->fetchAssoc("SELECT text FROM dev"));
        $this->assertEquals([[$testText]], $db->fetchArray("SELECT text FROM dev"));

        // update entry and check if it has been updated
        $this->assertTrue(
            $db->update("dev", ['text' => $testText2], "id = {0} || id = {anyparamname}", [1, "anyparamname" => 1])
        );
        $this->assertEquals([[$testText2]], $db->fetchArray("SELECT text FROM dev"));

        // delete the entry and check if it has been deleted
        $this->assertTrue(
            $db->delete("dev", "id = 1")
        );
        $this->assertEquals([], $db->fetchArray("SELECT text FROM dev"));
    }
}