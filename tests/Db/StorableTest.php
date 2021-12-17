<?php

namespace Framelix\Framelix\Tests\Db;

use Exception;
use Framelix\Framelix\Date;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\TestStorable1;
use Framelix\Framelix\Storable\TestStorable2;
use Framelix\Framelix\Time;
use PHPUnit\Framework\TestCase;
use function array_keys;
use function in_array;
use function str_repeat;
use function var_export;

final class StorableTest extends TestCase
{
    /**
     * Executed queries
     * @var int
     */
    private int $executedQueries = 0;

    /**
     * Some dummy values for tests
     * @var array
     */
    private array $dummyValues = [];

    /**
     * @runInSeparateProcess
     */
    public function testStoreAndDelete(): void
    {
        $db = Mysql::get('test');
        $db->query("DROP DATABASE `{$db->connectionConfig['database']}`");
        $db->query("CREATE DATABASE `{$db->connectionConfig['database']}`");
        $db->query("USE `{$db->connectionConfig['database']}`");
        $builder = new MysqlStorableSchemeBuilder($db);
        $builder->includeTestStorables = true;
        // first create all things
        $queries = $builder->getQueries();
        foreach ($queries as $queryData) {
            $db->query($queryData['query']);
        }

        $this->startRecordExecutedQueries();
        $storable = new TestStorable1();
        $storable->name = "foobar@dev.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime();
        $storable->date = new DateTime();
        $storable->store();
        $storableReference = $storable;
        // 1x to insert into id table, 1x to insert into storable table 1x to fetch storableClassId
        $this->assertExecutedQueries(3);

        $this->startRecordExecutedQueries();
        $storable = new TestStorable1();
        $storable->name = "foobar@test2.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime();
        $storable->date = new DateTime();
        $storable->selfReferenceOptional = $storableReference;
        $storable->store();
        $storable1 = $storable;
        // 1x to insert into id table, 1x to insert into storable table
        $this->assertExecutedQueries(2);
        $this->assertSame('foobar@test2.me',
            $db->fetchOne("SELECT name FROM framelix_framelix_storable_teststorable1 WHERE id = 2"));

        $this->startRecordExecutedQueries();
        $storable = new TestStorable2();
        $storable->name = "foobar@test2.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->longTextLazy = str_repeat("foo", 1000);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime("2000-01-01 12:23:44");
        $storable->date = Date::create("2000-01-01");
        $storable->otherReferenceOptional = $storableReference;
        $storable->otherReferenceArrayOptional = [$storableReference, $storableReference];
        $storable->typedIntArray = [1, 3, 5];
        $storable->typedBoolArray = [true, false, true];
        $storable->typedStringArray = ["yes", "baby", "yes"];
        $storable->typedFloatArray = [1.2, 1.6, 1.7];
        $storable->typedDateArray = [
            DateTime::create("2000-01-01 12:23:44"),
            DateTime::create("2000-01-01 12:23:44 + 10 days"),
            DateTime::create("2000-01-01 12:23:44 + 1 year")
        ];
        $storable->time = Time::create("12:00:01");
        $storable->store();
        $storable2 = $storable;
        $storableReference = $storable;
        // 1x to insert into id table, 1x to insert into storable table
        $this->assertExecutedQueries(2);
        $this->assertSame('foobar@test2.me',
            $db->fetchOne("SELECT name FROM framelix_framelix_storable_teststorable2 WHERE id = 3"));

        $this->startRecordExecutedQueries();
        $storable = new TestStorable2();
        $storable->name = "foobar@test3.me";
        $storable->longText = str_repeat("foo", 100);
        $storable->intNumber = 69;
        $storable->floatNumber = 6.9;
        $storable->boolFlag = true;
        $storable->jsonData = ['foobar', 1];
        $storable->dateTime = new DateTime();
        $storable->date = Date::create('now');
        $storable->selfReferenceOptional = $storableReference;
        $storable->store();
        // 1x to insert into id table, 1x to insert into storable table
        $this->assertExecutedQueries(2);
        $this->assertSame('foobar@test3.me',
            $db->fetchOne("SELECT name FROM framelix_framelix_storable_teststorable2 WHERE id = 4"));

        $this->startRecordExecutedQueries();
        $storable->name = "foobar@test4.me";
        $storable->store();
        // 1x to update
        $this->assertExecutedQueries(1);
        $this->assertSame('foobar@test4.me',
            $db->fetchOne("SELECT name FROM framelix_framelix_storable_teststorable2 WHERE id = 4"));

        $this->startRecordExecutedQueries();
        $storable->store();
        // nothing changed no query to execute
        $this->assertExecutedQueries(0);

        $this->startRecordExecutedQueries();
        $storable->delete();
        // delete from id table and actual storable table
        $this->assertExecutedQueries(2);
        $this->assertSame(null,
            $db->fetchOne("SELECT name FROM framelix_framelix_storable_teststorable2 WHERE id = 4"));

        $storable->store();

        // create more storables for next dev
        for ($i = 0; $i <= 50; $i++) {
            $storableNew = $storable2->clone();
            $storableNew->otherReferenceOptional = $storable1;
            $storableNew->selfReferenceOptional = $storable2;
            $storableNew->store();
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testFetch(): void
    {
        // fetch last 50 teststorables1 as they all have the same data applied
        // which makes checking for getter values easy here
        $this->startRecordExecutedQueries();
        $storables = TestStorable2::getByCondition(sort: "-id", limit: 50);
        $this->assertExecutedQueries(1);
        $this->assertCount(50, $storables);
        $this->startRecordExecutedQueries();
        foreach ($storables as $storable) {
            $this->assertSame("foobar@test2.me", $storable->name);
            $this->assertSame(str_repeat("foo", 100), $storable->longText);
            $this->assertSame(69, $storable->intNumber);
            $this->assertSame(6.9, $storable->floatNumber);
            $this->assertSame(true, $storable->boolFlag);
            $this->assertSame(['foobar', 1], $storable->jsonData);
            $this->assertSame("2000-01-01 12:23:44", $storable->dateTime->format("Y-m-d H:i:s"));
            $this->assertSame("2000-01-01", $storable->dateTime->format("Y-m-d"));
            $this->assertSame([1, 3, 5], $storable->typedIntArray);
            $this->assertSame([true, false, true], $storable->typedBoolArray);
            $this->assertSame(["yes", "baby", "yes"], $storable->typedStringArray);
            $this->assertSame([1.2, 1.6, 1.7], $storable->typedFloatArray);
            $this->assertEquals(Time::create("12:00:01"), $storable->time);
            $this->assertEquals(Date::create("2000-01-01"), $storable->date);
            $this->assertEquals([
                DateTime::create("2000-01-01 12:23:44"),
                DateTime::create("2000-01-01 12:23:44 + 10 days"),
                DateTime::create("2000-01-01 12:23:44 + 1 year")
            ], $storable->typedDateArray);
            $this->assertNull($storable->dateOptional);
            $this->assertNull($storable->longTextOptional);
            $this->assertNull($storable->boolFlagOptional);
            $this->assertNull($storable->dateTimeOptional);
            $this->assertNull($storable->floatNumberOptional);
            $this->assertNull($storable->intNumberOptional);
            $this->assertNull($storable->jsonDataOptional);
            if ($storable->selfReferenceOptional) {
                $this->assertInstanceOf(TestStorable2::class, $storable->selfReferenceOptional);
                $this->assertSame(3, $storable->selfReferenceOptional->id);
            }
            if ($storable->otherReferenceOptional) {
                $this->assertInstanceOf(TestStorable1::class, $storable->otherReferenceOptional);
                $this->assertSame(2, $storable->otherReferenceOptional->id);
            }
            if ($storable->otherReferenceArrayOptional) {
                $otherReference = TestStorable1::getById(1);
                $this->assertIsArray($storable->otherReferenceArrayOptional);
                $this->assertSame([$otherReference, $otherReference],
                    $storable->otherReferenceArrayOptional);
            }
        }
        // for each call on a referenced property of on type of class there is only one query needed
        // no matter how many storables are processed
        $this->assertExecutedQueries(3);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDepthFetch(): void
    {
        $storables = TestStorable2::getByCondition("selfReferenceOptional.selfReferenceOptional IS NULL && selfReferenceOptional.createTime IS NOT NULL && selfReferenceOptional.updateTime IS NOT NULL && otherReferenceOptional.createTime IS NOT NULL && otherReferenceOptional.selfReferenceOptional.createTime IS NOT NULL");
        $this->assertGreaterThan(0, count($storables));
    }

    /**
     * @runInSeparateProcess
     */
    public function testFetchChildsOfAbstractStorables(): void
    {
        // fetching all storables are effectively all that exist
        $storables = Storable::getByCondition(connectionId: "test");
        $this->assertCount((int)Mysql::get('test')->fetchOne('SELECT COUNT(*) FROM framelix__id'), $storables);
        $extendedCount = 0;
        foreach ($storables as $storable) {
            if ($storable instanceof StorableExtended) {
                $extendedCount++;
            }
        }
        $storables = StorableExtended::getByCondition("createTime IS NOT NULL", connectionId: "test");
        $this->assertCount($extendedCount, $storables);

        $storables = StorableExtended::getByIds([3, 5, 6], connectionId: "test");
        $this->assertSame([3, 5, 6], array_keys($storables));
    }

    /**
     * @runInSeparateProcess
     */
    public function testDatatypesSetter(): void
    {
        $this->dummyValues = [
            'int' => 1,
            'float' => 1.0,
            'string' => "foo",
            'array' => ["blub"],
            'mixed' => ["blub"],
            DateTime::class => new DateTime(),
            TestStorable2::class => new TestStorable2()
        ];
        $storable = TestStorable2::getByConditionOne(condition: "-id", sort: "-id");
        $this->assertStorablePropertyValueSetter($storable, "id", ['int']);
        $this->assertStorablePropertyValueSetter($storable, "name", ['string']);
        $this->assertStorablePropertyValueSetter($storable, "intNumber", ['int']);
        $this->assertStorablePropertyValueSetter($storable, "floatNumber", ['float']);
        $this->assertStorablePropertyValueSetter($storable, "boolFlag", ['bool']);
        $this->assertStorablePropertyValueSetter($storable, "jsonData", array_keys($this->dummyValues));
        $this->assertStorablePropertyValueSetter($storable, "selfReferenceOptional", [TestStorable2::class]);
        $this->assertStorablePropertyValueSetter($storable, "dateTime", [DateTime::class]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDatatypesGetter(): void
    {
        $storable = TestStorable2::getByConditionOne("selfReferenceOptional IS NOT NULL && longTextLazy IS NOT NULL",
            sort: "+id");
        $this->assertIsInt($storable->id);
        $this->assertIsString($storable->name);
        $this->assertIsFloat($storable->floatNumber);
        $this->assertIsInt($storable->intNumber);
        $this->assertIsBool($storable->boolFlag);
        $this->assertInstanceOf(TestStorable2::class, $storable->selfReferenceOptional);
        $this->assertNull($storable->createUser);
        $this->assertInstanceOf(DateTime::class, $storable->dateTime);
        // test lazy
        $this->assertNull($storable->getOriginalDbValueForProperty("longTextLazy"));
        $this->assertIsString($storable->longTextLazy);
        $this->assertIsString($storable->getOriginalDbValueForProperty("longTextLazy"));
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteAll(): void
    {
        Storable::deleteMultiple(TestStorable1::getByCondition());
        $this->assertCount(0, TestStorable1::getByCondition());
    }

    /**
     * @runInSeparateProcess
     */
    public function testLazy(): void
    {
        Storable::deleteMultiple(TestStorable1::getByCondition());
        $this->assertCount(0, TestStorable1::getByCondition());
    }


    private function assertStorablePropertyValueSetter(
        Storable $storable,
        string $propertyName,
        array $allowedTypes
    ): void {
        foreach ($allowedTypes as $allowedType) {
            $storable->{$propertyName} = $this->dummyValues[$allowedType];
        }
        foreach ($this->dummyValues as $type => $value) {
            if (in_array($type, $allowedTypes)) {
                continue;
            }
            $valid = true;
            try {
                $storable->{$propertyName} = $value;
                $valid = false;
            } catch (Exception $e) {
            }
            $this->assertTrue($valid, "Property: $propertyName, Type: $type, Got: " . var_export($value, true));
        }
    }

    private function startRecordExecutedQueries(): void
    {
        $db = Mysql::get('test');
        $this->executedQueries = $db->executedQueriesCount;
    }

    private function assertExecutedQueries(int $queries): void
    {
        $db = Mysql::get('test');
        $this->assertSame($queries, $db->executedQueriesCount - $this->executedQueries);
    }
}