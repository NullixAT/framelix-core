<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\Config;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function array_keys;
use function array_reverse;
use function array_values;
use function explode;
use function implode;
use function is_int;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

/**
 * Mysql scheme builder for storables
 * Automatically updates/create/modifies table schema in the database
 */
class MysqlStorableSchemeBuilder
{
    public const DEFAULT_COLLATION = 'utf8mb4_unicode_ci';
    public const DEFAULT_ENGINE = 'InnoDB';

    /**
     * Constructor
     * @param Mysql $db
     */
    public function __construct(public Mysql $db)
    {
    }

    /**
     * Execute given builder queries
     * @param array $queries
     * @return void
     */
    public function executeQueries(array $queries): void
    {
        foreach ($queries as $row) {
            if ($row['ignoreErrors'] ?? null) {
                try {
                    $this->db->queryRaw($row['query']);
                } catch (Exception) {
                }
            } else {
                $this->db->queryRaw($row['query']);
            }
        }
    }

    /**
     * Get queries that are unsafe to execute, queries that will modifiy/delete existing columns
     * Unsafe queries are only returned when no safe queries exist, as it is possible that safe queries obsoletes unsafe ones
     * @return array
     */
    public function getUnsafeQueries(): array
    {
        if ($this->getSafeQueries()) {
            return [];
        }
        // re-index
        return array_values($this->getQueries());
    }

    /**
     * Get only queries that are safe to execute
     * Queries that modify/delete any existing data will not be returned
     * @return array
     */
    public function getSafeQueries(): array
    {
        $queries = $this->getQueries();
        foreach ($queries as $key => $row) {
            if (!str_starts_with($row['type'], "create")) {
                unset($queries[$key]);
            }
        }
        // re-index
        return array_values($queries);
    }

    /**
     * Get all queries that are required to update the database
     * @return array
     */
    public function getQueries(): array
    {
        $requiredStorableSchemas = [];
        $queries = [];
        $existingTables = [];
        $fetch = $this->db->fetchAssoc("SHOW TABLE STATUS FROM `{$this->db->connectionConfig['database']}`");
        foreach ($fetch as $row) {
            $tableName = strtolower($row['Name']);
            $existingTables[$tableName] = $tableName;
        }
        /** @var StorableSchema[] $existingStorableSchemas */
        $existingStorableSchemas = [];

        // get all existing properties and indexes
        foreach ($existingTables as $existingTable) {
            $storableSchema = new StorableSchema($existingTable);
            $existingStorableSchemas[$existingTable] = $storableSchema;
            $rows = $this->db->fetchAssoc("SHOW FULL FIELDS FROM `$existingTable`");
            foreach ($rows as $row) {
                $storableSchemaProperty = $storableSchema->createProperty($row['Field']);
                $type = $row["Type"];
                $unsignedPos = strpos($type, "unsigned");
                if ($unsignedPos !== false) {
                    $type = substr($type, 0, strpos($type, "unsigned"));
                }
                $typeExp = explode("(", trim($type, "()"));
                $length = isset($typeExp[1]) ? explode(",", $typeExp[1]) : [];
                $type = trim($typeExp[0]);
                $storableSchemaProperty->databaseType = $type;
                if (isset($length[0])) {
                    $storableSchemaProperty->length = (int)$length[0];
                }
                if (isset($length[1])) {
                    $storableSchemaProperty->decimals = (int)$length[1];
                }
                if ($row["Null"] === "YES") {
                    $storableSchemaProperty->allowNull = true;
                }
                $storableSchemaProperty->unsigned = $unsignedPos !== false;
                $storableSchemaProperty->autoIncrement = str_contains($row["Extra"], "auto_increment");
                $storableSchemaProperty->dbComment = $row['Comment'] ?: null;
            }
            $rows = $this->db->fetchAssoc("SHOW INDEXES FROM `$existingTable`");
            foreach ($rows as $row) {
                // just store that we have an index with this name, doesnt matter which because we skip if index already exist
                $storableSchema->addIndex($row["Key_name"], 'index');
            }
        }

        // framelix internal tables
        $storableSchema = new StorableSchema(StorableSchema::ID_TABLE);
        $storableSchema->addIndex('storableId', 'index');
        $requiredStorableSchemas[$storableSchema->tableName] = $storableSchema;

        $storableSchemaProperty = $storableSchema->createProperty('id');
        $storableSchemaProperty->databaseType = 'bigint';
        $storableSchemaProperty->length = 18;
        $storableSchemaProperty->unsigned = true;
        $storableSchemaProperty->autoIncrement = true;

        $storableSchemaProperty = $storableSchema->createProperty('storableId');
        $storableSchemaProperty->databaseType = 'int';
        $storableSchemaProperty->length = 5;
        $storableSchemaProperty->unsigned = true;

        $storableSchema = new StorableSchema(StorableSchema::SCHEMA_TABLE);
        $storableSchema->addIndex('storableClass', 'unique');
        $requiredStorableSchemas[$storableSchema->tableName] = $storableSchema;

        $storableSchemaProperty = $storableSchema->createProperty('id');
        $storableSchemaProperty->databaseType = 'bigint';
        $storableSchemaProperty->length = 18;
        $storableSchemaProperty->unsigned = true;
        $storableSchemaProperty->autoIncrement = true;

        $storableSchemaProperty = $storableSchema->createProperty('storableClass');
        $storableSchemaProperty->databaseType = 'varchar';
        $storableSchemaProperty->length = 191;

        $storableSchemaProperty = $storableSchema->createProperty('storableClassParents');
        $storableSchemaProperty->databaseType = 'text';

        // fetch all storable schemas
        foreach (Config::$loadedModules as $module) {
            $moduleFolder = FileUtils::getModuleRootPath($module);
            $storableFiles = FileUtils::getFiles("$moduleFolder/src/Storable", "~\.php$~", true);
            foreach ($storableFiles as $storableFile) {
                $storableClass = ClassUtils::getClassNameForFile($storableFile);
                $storableSchema = Storable::getStorableSchema($storableClass);
                // abstract classes are not need to be created
                if ($storableSchema->abstract) {
                    continue;
                }
                $requiredStorableSchemas[$storableSchema->tableName] = $storableSchema;
            }
        }

        // at first create all required tables with the id column
        foreach ($requiredStorableSchemas as $tableName => $storableSchema) {
            if (isset($existingTables[$tableName])) {
                continue;
            }
            $query = "CREATE TABLE `$storableSchema->tableName` (`id` BIGINT UNSIGNED NOT NULL";
            if ($storableSchema->properties['id']->autoIncrement) {
                $query .= " AUTO_INCREMENT";
            }
            $query .= ", PRIMARY KEY (`id`) USING BTREE) COLLATE='" . self::DEFAULT_COLLATION . "' ENGINE=" . self::DEFAULT_ENGINE;
            $queries[] = [
                "type" => 'create-table',
                "query" => $query
            ];
        }

        // creating columns and indexes
        foreach ($requiredStorableSchemas as $tableName => $storableSchema) {
            $existingStorableSchema = $existingStorableSchemas[$tableName] ?? null;
            foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
                // id field is already created by previous create table action
                if ($propertyName === 'id') {
                    continue;
                }
                $queryPartExisting = null;
                $existingProperty = $existingStorableSchema->properties[$propertyName] ?? null;
                if ($existingProperty) {
                    $queryPartExisting = self::getQueryForAlterTableColumn(
                        $existingProperty,
                        false
                    );
                }
                $queryPartNew = self::getQueryForAlterTableColumn($storableSchemaProperty, !$existingProperty);
                if ($queryPartExisting !== $queryPartNew) {
                    $query = "ALTER TABLE `$tableName` " . $queryPartNew;
                    $queries[] = [
                        "type" => $queryPartExisting ? 'alter-column' : 'create-column',
                        "query" => $query
                    ];
                }
            }
            if ($storableSchema->indexes) {
                foreach ($storableSchema->indexes as $indexName => $indexOptions) {
                    // if an index exist, skip, we not allow modifying it
                    if (isset($existingStorableSchema->indexes[$indexName])) {
                        continue;
                    }
                    $query = "ALTER TABLE `$tableName` " . self::getQueryForAddTableIndex(
                            $indexName,
                            $indexOptions
                        );
                    $queries[] = [
                        "type" => 'create-index',
                        "query" => $query
                    ];
                }
            }
            // adding required meta storable class
            if ($storableSchema->tableName !== StorableSchema::ID_TABLE && $storableSchema->tableName !== StorableSchema::SCHEMA_TABLE) {
                $storableClassParents = $this->db->escapeValue(
                    JsonUtils::encode(array_reverse(array_keys($storableSchema->parentStorableSchemas)))
                );
                $checkQuery = '
                    SELECT storableClassParents FROM ' . StorableSchema::SCHEMA_TABLE . ' WHERE storableClass = ' . $this->db->escapeValue(
                        $storableSchema->className
                    ) . '
                ';
                if (!isset($existingTables[StorableSchema::SCHEMA_TABLE]) || $this->db->escapeValue(
                        $this->db->fetchOne($checkQuery)
                    ) !== $storableClassParents) {
                    $queries[] = [
                        "type" => 'create-schema',
                        "query" => 'INSERT INTO ' . StorableSchema::SCHEMA_TABLE . ' (storableClass, storableClassParents) VALUES (' . $this->db->escapeValue(
                                $storableSchema->className
                            ) . ', ' . $storableClassParents . ') ON DUPLICATE KEY UPDATE storableClassParents = ' . $storableClassParents
                    ];
                }
            }
        }
        foreach ($existingTables as $existingTable) {
            // only obsolete framelix tables can be deleted
            if (!str_starts_with($existingTable, "framelix_")) {
                continue;
            }
            if (!isset($requiredStorableSchemas[$existingTable])) {
                $queries[] = [
                    "type" => 'drop-table',
                    "query" => "DROP TABLE `$existingTable`"
                ];
            }
        }
        foreach ($existingStorableSchemas as $tableName => $storableSchema) {
            // only obsolete framelix tables can be deleted
            if (!str_starts_with($tableName, "framelix_")) {
                continue;
            }
            // whole table will be dropped already
            if (!isset($requiredStorableSchemas[$tableName])) {
                continue;
            }
            foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
                if ($propertyName === 'id') {
                    continue;
                }
                if (!isset($requiredStorableSchemas[$tableName]->properties[$propertyName])) {
                    $queries[] = [
                        "type" => 'drop-column',
                        "query" => "ALTER TABLE `$tableName` DROP COLUMN `$propertyName`",
                        "ignoreErrors" => true,
                    ];
                }
            }
            foreach ($storableSchema->indexes as $indexName => $indexOptions) {
                // don't touch primary indexes
                if ($indexOptions['type'] === 'primary' || strtolower($indexName) === 'primary') {
                    continue;
                }
                if (!isset($requiredStorableSchemas[$tableName]->indexes[$indexName])) {
                    $queries[] = [
                        "type" => 'drop-index',
                        "query" => "ALTER TABLE `$tableName` DROP INDEX `$indexName`",
                        "ignoreErrors" => true
                    ];
                }
            }
        }
        return $queries;
    }

    /**
     * Get query part for alter table column
     * @param StorableSchemaProperty $storableSchemaProperty
     * @param bool $isNewColumn
     * @return string
     */
    private function getQueryForAlterTableColumn(
        StorableSchemaProperty $storableSchemaProperty,
        bool $isNewColumn
    ): string {
        $queryColumnParts = [];
        $queryColumnParts[] = !$isNewColumn ? 'CHANGE COLUMN' : 'ADD';
        $queryColumnParts[] = "`$storableSchemaProperty->name`";
        if (!$isNewColumn) {
            $queryColumnParts[] = "`$storableSchemaProperty->name`";
        }
        $databaseType = strtoupper($storableSchemaProperty->databaseType);
        $queryColumnParts[] = strtoupper($storableSchemaProperty->databaseType);

        $lengthAllowed =
            $databaseType === "FLOAT"
            || $databaseType === "DOUBLE"
            || $databaseType === "DECIMAL"
            || $databaseType === "TINYINT"
            || str_ends_with($databaseType, "CHAR")
            || str_ends_with($databaseType, "BINARY");
        if ($lengthAllowed && is_int($storableSchemaProperty->length)) {
            if (is_int($storableSchemaProperty->decimals)) {
                $queryColumnParts[] = "($storableSchemaProperty->length, $storableSchemaProperty->decimals)";
            } else {
                $queryColumnParts[] = "($storableSchemaProperty->length)";
            }
        }
        if ($storableSchemaProperty->unsigned) {
            $queryColumnParts[] = "UNSIGNED";
        }
        $queryColumnParts[] = ($storableSchemaProperty->allowNull ? 'NULL' : 'NOT NULL');
        // here would be auto_increment but we do not need to support alerting tables with auto_increment
        // there is only one table with AI which is always created by default
        if ($storableSchemaProperty->dbComment) {
            $queryColumnParts[] = "COMMENT " . $this->db->escapeValue($storableSchemaProperty->dbComment);
        }
        if ($storableSchemaProperty->after) {
            $queryColumnParts[] = "AFTER `{$storableSchemaProperty->after->name}`";
        }
        return implode(" ", $queryColumnParts);
    }

    /**
     * Get query part for add table index
     * @param string $indexName
     * @param array $options
     * @return string
     */
    private function getQueryForAddTableIndex(string $indexName, array $options): string
    {
        $queryIndexParts = ["ADD"];
        if ($options['type'] === 'index') {
            $queryIndexParts[] = "INDEX";
        } elseif ($options['type'] === 'unique') {
            $queryIndexParts[] = "UNIQUE INDEX";
        } elseif ($options['type'] === 'fulltext') {
            $queryIndexParts[] = "FULLTEXT INDEX";
        }
        $columnNames = [];
        foreach ($options['properties'] as $columnName) {
            $columnNames[] = "`$columnName`";
        }
        if ($options['type'] !== 'primary') {
            $queryIndexParts[] = "`$indexName`";
        }
        $queryIndexParts[] = "(" . implode(", ", $columnNames) . ")";
        return implode(" ", $queryIndexParts);
    }
}