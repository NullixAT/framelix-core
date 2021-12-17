<?php

namespace Framelix\Framelix\Tests\Db;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use PHPUnit\Framework\TestCase;
use function var_dump;

final class StorableSchemeBuilderTest extends TestCase
{
    /**
     * Drop everything before starting the tests
     */
    public static function setUpBeforeClass(): void
    {
        $db = Mysql::get('test');
        $db->query("DROP DATABASE `{$db->connectionConfig['database']}`");
        $db->query("CREATE DATABASE `{$db->connectionConfig['database']}`");
        $db->query("USE `{$db->connectionConfig['database']}`");
    }

    public function testMisc(): void
    {
        $db = Mysql::get('test');
        $builder = new MysqlStorableSchemeBuilder($db);
        $builder->includeTestStorables = true;
        // first create all things
        $queries = $builder->getQueries();
        foreach ($queries as $queryData) {
            $db->query($queryData['query']);
        }
        // calling the builder immediately after should not need to change anything
        $queries = $builder->getQueries();
        $this->assertQueryCount(0, $queries, true);
        // deleting a column and than the builder should recreate this including the index
        // 3 queries because 1x adding, 1x reordering columns and 1x creating an index
        $db->query("ALTER TABLE framelix_framelix_storable_teststorable1 DROP COLUMN `createUser`");
        $queries = $builder->getQueries();
        $this->assertQueryCount(3, $queries, true);
        // modifying some table data to simulate changed property behaviour
        $db->query('ALTER TABLE `framelix_framelix_storable_teststorable1`
	CHANGE COLUMN `createTime` `createTime` DATE NULL DEFAULT NULL AFTER `id`,
	CHANGE COLUMN `longText` `longText` VARCHAR(50) NULL DEFAULT NULL COLLATE \'utf8mb4_unicode_ci\' AFTER `name`,
	CHANGE COLUMN `selfReferenceOptional` `selfReferenceOptionals` BIGINT(18) UNSIGNED NULL DEFAULT NULL,
	DROP INDEX `selfReferenceOptional`');

        $queries = $builder->getQueries();
        $this->assertQueryCount(6, $queries, true);

        // calling the builder immediately after should not need to change anything
        $queries = $builder->getQueries();
        $this->assertQueryCount(0, $queries, true);
        // droping an index and let the system recreate it
        $db->query("ALTER TABLE `framelix_framelix_storable_user` DROP INDEX `updateUser`");
        $queries = $builder->getQueries();
        $this->assertQueryCount(1, $queries, true);
        // adding some additional obsolete columns and tables that the builder should delete
        $db->query("ALTER TABLE `framelix_framelix_storable_user`
	ADD COLUMN `unusedTime` DATETIME NULL DEFAULT NULL,
	ADD INDEX `flagLocked` (`flagLocked`)");
        $db->query('CREATE TABLE `framelix_unused_table` (`id` INT(11) NULL DEFAULT NULL)');
        $queries = $builder->getQueries();
        $this->assertQueryCount(3, $queries, true);
    }

    /**
     * Assert special query count which ignores some irrelevant queries
     * @param int $count
     * @param array $queries
     * @param bool $execute Execute queries after assert
     */
    private function assertQueryCount(int $count, array $queries, bool $execute): void
    {
        foreach ($queries as $key => $row) {
            // insert metas are ignored, as they are always here
            if ($row['type'] === 'insert-meta') {
                unset($queries[$key]);
            }
        }
        $this->assertCount($count, $queries);
        if ($execute) {
            foreach ($queries as $queryData) {
                Mysql::get('test')->query($queryData['query']);
            }
        }
    }
}