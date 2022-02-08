<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Db\StorableSchemaProperty;
use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\ObjectTransformable;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\JsonUtils;
use JsonSerializable;
use ReflectionClass;

use function array_pop;
use function array_reverse;
use function array_unique;
use function array_values;
use function call_user_func;
use function call_user_func_array;
use function class_exists;
use function count;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function reset;
use function substr;
use function trim;

/**
 * Base Storable
 * @property int|null $id
 */
abstract class Storable implements JsonSerializable, ObjectTransformable
{

    /**
     * Deactivating the prefetch behaviour globally if you need to here
     * @var bool
     */
    public static bool $prefetchEnabled = true;

    /**
     * Internal db cache
     * @var array
     */
    private static $dbCache = [];

    /**
     * Internal schema cache
     * @var array
     */
    private static array $schemaCache = [];

    /**
     * Internal schema table cache
     * @var array
     */
    private static array $schemaTableCache = [];

    /**
     * The db connection id for this storable
     * @var string
     */
    public string $connectionId;

    /**
     * Property cache
     * @var array
     */
    protected array $propertyCache = [];

    /**
     * Get connection that is default responsible for the called storable
     * @return string
     */
    final public static function getConnectionId(): string
    {
        return self::getStorableSchema(static::class)->connectionId ?? "default";
    }

    /**
     * Get a single storable by id and return an empty instance if not found
     * @param string|int|null $id
     * @param string|null $connectionId Database connection id to use
     * @return static
     */
    final public static function getByIdOrNew(mixed $id, ?string $connectionId = null): static
    {
        return self::getById($id, $connectionId) ?? new static();
    }

    /**
     * Get a single storable by id
     * @param string|int|null $id
     * @param string|null $connectionId Database connection id to use
     * @return static|null
     */
    final public static function getById(mixed $id, ?string $connectionId = null): ?static
    {
        if (!$id || !is_numeric($id)) {
            return null;
        }
        return static::getByIds([$id], $connectionId)[$id] ?? null;
    }

    /**
     * Get storables by given array of ids
     * @param array|null $ids
     * @param string|null $connectionId Database connection id to use
     * @return static[]
     */
    final public static function getByIds(?array $ids, ?string $connectionId = null): array
    {
        if (!$ids) {
            return [];
        }
        $connectionId = $connectionId ?? static::getConnectionId();
        $storables = [];
        $cachedStorables = self::$dbCache[$connectionId][static::class] ?? [];
        $idsRest = $ids;
        foreach ($ids as $key => $id) {
            if (!$id || !is_numeric($id)) {
                unset($idsRest[$key]);
                continue;
            }
            if (isset($cachedStorables[$id])) {
                $storables[$id] = $cachedStorables[$id];
                unset($idsRest[$key]);
            }
        }
        // ids to fetch from database
        if ($idsRest) {
            $storables = ArrayUtils::merge(
                $storables,
                static::getByCondition('id IN {0}', [$idsRest], connectionId: $connectionId)
            );
        }
        // keep original ids sort
        $returnArr = [];
        foreach ($ids as $id) {
            if (isset($storables[$id])) {
                $returnArr[$id] = $storables[$id];
            }
        }
        return $returnArr;
    }

    /**
     * Get a single storable by a condition
     * @param string|null $condition
     * @param array|null $parameters
     * @param array|string|null $sort
     * @param int|null $offset $offset
     * @param string|null $connectionId Database connection id to use
     * @return static|null
     */
    final public static function getByConditionOne(
        ?string $condition = null,
        ?array $parameters = null,
        array|string|null $sort = null,
        ?int $offset = null,
        ?string $connectionId = null
    ): ?static {
        $arr = static::getByCondition($condition, $parameters, $sort, 1, $offset, $connectionId);
        if ($arr) {
            return reset($arr);
        }
        return null;
    }

    /**
     * Get array of storables by a condition
     * @param string|null $condition
     * @param array|null $parameters
     * @param array|string|null $sort
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $connectionId Database connection id to use
     * @return static[]
     */
    final public static function getByCondition(
        ?string $condition = null,
        ?array $parameters = null,
        array|string|null $sort = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $connectionId = null
    ): array {
        $storableSchema = static::getStorableSchema();
        $db = Mysql::get($connectionId ?? static::getConnectionId());
        // abstract classes can not be directly fetched from the database, as they not exist
        // we consider abstract class fetch to want to fetch all its childs that met the condition
        if ($storableSchema->abstract) {
            return self::fetchAbstractClassChilds(
                $db->id,
                static::class,
                "getByCondition",
                $condition,
                $parameters,
                $sort,
                $limit,
                $offset,
                $db->id
            );
        }
        $properties = [];
        foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
            // ignore lazy fetch, it is fetched when actually calling the property
            if ($storableSchemaProperty->lazyFetch) {
                continue;
            }
            $properties[] = "`t0`.`$propertyName`";
        }
        if ($sort) {
            if (!is_array($sort)) {
                $sort = [$sort];
            }
        }
        $query = "SELECT " . implode(
                ", ",
                $properties
            ) . "\nFROM `$storableSchema->tableName` as t0\n";


        $querySearch = "";
        if ($condition) {
            $querySearch .= $condition;
        }
        if ($sort) {
            $querySearch .= implode(", ", $sort);
        }
        // depth joins
        // find all conditions that are concated with a dot
        // each part represent the nested storable reference from the storable
        // so example: 'createUser.email IS NULL' will find each storable where its create user email is null
        // this will get automatically joined by this technique
        $depthJoinSearchReplace = [];
        if ($querySearch) {
            preg_match_all("~([a-z0-9_]+\.[^\s]+)~i", $querySearch, $depthMatches);
            if ($depthMatches[0]) {
                $uniqueMatches = [];
                foreach ($depthMatches[0] as $value) {
                    $uniqueMatches[$value] = $value;
                }
                $tableAliasToDepthPath = [];
                $joinCount = 1;
                foreach ($uniqueMatches as $uniqueMatch) {
                    $parts = explode(".", $uniqueMatch);
                    $partStorableSchema = $storableSchema;
                    $depthPath = "";
                    $prevDepthPath = "";
                    $lastPart = array_pop($parts);
                    $aliasTableName = null;
                    foreach ($parts as $partPropertyName) {
                        $depthPath .= $partPropertyName . "-";
                        $partStorableSchemaProperty = $partStorableSchema->properties[$partPropertyName] ?? null;
                        // skip if property has not been found
                        if (!$partStorableSchemaProperty || (!$partStorableSchemaProperty->storableClass && !$partStorableSchemaProperty->arrayStorableClass)) {
                            $aliasTableName = null;
                            continue;
                        }
                        $partStorableSchema = Storable::getStorableSchema(
                            $partStorableSchemaProperty->storableClass ?? $partStorableSchemaProperty->arrayStorableClass
                        );
                        $prevAliasTableName = !$prevDepthPath ? "t0" : $tableAliasToDepthPath[$prevDepthPath];
                        $aliasTableName = $tableAliasToDepthPath[$depthPath] ?? null;
                        if (!$aliasTableName) {
                            $aliasTableName = "t" . $joinCount;
                            $query .= "LEFT JOIN `$partStorableSchema->tableName` as `$aliasTableName` ";
                            $query .= "ON ";
                            if ($partStorableSchemaProperty->arrayStorableClass) {
                                $query .= " JSON_CONTAINS(CAST(`$prevAliasTableName`.`$partStorableSchemaProperty->name` as CHAR), CAST(`$aliasTableName`.`id` as CHAR), '\$')";
                            } else {
                                $query .= " `$aliasTableName`.`id` = `$prevAliasTableName`.`$partStorableSchemaProperty->name`";
                            }
                            $query .= "\n";
                            $tableAliasToDepthPath[$depthPath] = $aliasTableName;
                            $joinCount++;
                        }
                        $prevDepthPath = $depthPath;
                    }
                    if ($aliasTableName) {
                        $depthJoinSearchReplace[$uniqueMatch] = "`$aliasTableName`.`$lastPart`";
                    }
                }
            }
        }
        if ($condition) {
            $query .= "WHERE $condition\n";
        }
        $query .= "GROUP BY t0.id\n";
        if ($sort) {
            $query .= "ORDER BY ";
            foreach ($sort as $sortProperty) {
                if ($sortProperty[0] !== "-" && $sortProperty[0] !== "+") {
                    throw new Exception(
                        "Sort properties must begin with -/+ to indicate sort direction",
                        ErrorCode::STORABLE_SORT_DIRECTION_MISSING
                    );
                }
                $query .= "`" . substr($sortProperty, 1) . "` " . ($sortProperty[0] === "+" ? "ASC" : "DESC") . ", ";
            }
            $query = trim($query, ", ") . "\n";
        }
        if (is_int($limit)) {
            $query .= "LIMIT $limit\n";
        }
        if (is_int($offset)) {
            $query .= "OFFSET $limit\n";
        }
        if ($depthJoinSearchReplace) {
            foreach ($depthJoinSearchReplace as $search => $replace) {
                $query = preg_replace(
                    "~(^|\s+|\()" . preg_quote($search, "~") . "(\s+|$|\))~i",
                    "$1$replace$2",
                    $query
                );
            }
        }
        $query = $db->replaceParameters($query, $parameters);
        $dbFetch = $db->fetchAssoc($query);
        $storables = [];
        foreach ($dbFetch as $row) {
            // re-use already cached storables
            if (isset(self::$dbCache[$db->id][static::class][$row['id']])) {
                $storable = self::$dbCache[$db->id][static::class][$row['id']];
                $storables[$storable->id] = $storable;
            } else {
                $storable = new static();
                $storable->connectionId = $db->id;
                foreach ($row as $key => $value) {
                    $storable->propertyCache['dbvalue'][$key] = $value;
                    $storables[$storable->id] = $storable;
                    self::$dbCache[$db->id][get_class($storable)][$storable->id] = $storable;
                }
            }
        }
        return $storables;
    }

    /**
     * Delete multiple storables
     * @param self[]|null $storables
     */
    final public static function deleteMultiple(?array $storables): void
    {
        if (!is_array($storables)) {
            return;
        }
        foreach ($storables as $storable) {
            $storable->delete();
        }
    }

    /**
     * Get mysql table name to this storable
     * @param string|Storable $storable
     * @return string
     */
    final public static function getTableName(string|Storable $storable): string
    {
        return self::getStorableSchema($storable)->tableName;
    }

    /**
     * Get storable scehama for given class
     * @param string|Storable|null $storable If null than is called class
     * @return StorableSchema
     */
    final public static function getStorableSchema(string|Storable|null $storable = null): StorableSchema
    {
        if (!$storable) {
            $storable = static::class;
        }
        if (is_object($storable)) {
            $storable = get_class($storable);
        }
        $cacheKey = "schema-" . $storable;
        if (isset(self::$schemaCache[$cacheKey])) {
            return self::$schemaCache[$cacheKey];
        }
        $reflectionClass = new ReflectionClass($storable);
        $parent = $reflectionClass;
        $parentReflections = [];
        while ($parent = $parent->getParentClass()) {
            $parentReflections[] = $parent;
        }
        $parentReflections = array_reverse($parentReflections);
        $storableSchema = new StorableSchema($storable);
        foreach ($parentReflections as $parentReflection) {
            $storableSchema->mergeParent(self::getStorableSchema($parentReflection->getName()));
        }
        $storableSchema->parseClassData();
        call_user_func([$storable, "setupStorableSchema"], $storableSchema);
        self::$schemaCache[$cacheKey] = $storableSchema;
        return $storableSchema;
    }

    /**
     * Get storable schema property for given class property
     * @param string|Storable $storable
     * @param string $property
     * @return StorableSchemaProperty|null
     */
    final public static function getStorableSchemaProperty(
        string|Storable $storable,
        string $property
    ): ?StorableSchemaProperty {
        return self::getStorableSchema($storable)->properties[$property] ?? null;
    }

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
    }

    /**
     * Fetch the whole schema table if not yet fetched
     */
    private static function fetchSchemaTable(string $connectionId): void
    {
        if (!isset(self::$schemaTableCache[$connectionId])) {
            self::$schemaTableCache[$connectionId] = [];
            $fetch = Mysql::get($connectionId)->fetchAssoc(
                "
                SELECT * FROM " . StorableSchema::SCHEMA_TABLE . "
            "
            );
            foreach ($fetch as $row) {
                self::$schemaTableCache[$connectionId][$row['storableClass']] = [
                    'id' => (int)$row['id'],
                    'parents' => []
                ];
                self::$schemaTableCache[$connectionId][$row['storableClass']]['parents'] = JsonUtils::decode(
                    $row['storableClassParents']
                );
            }
        }
    }

    /**
     * Fetch all storable that have the given abstract class as a parent
     * @param string $connectionId
     * @param string $abstractClass
     * @param string $method The internal method to call for each class (getByCondition, getById, ...)
     * @param mixed ...$parameters All parameters to pass by
     * @return static[]
     */
    private static function fetchAbstractClassChilds(
        string $connectionId,
        string $abstractClass,
        string $method,
        ...$parameters
    ): array {
        self::fetchSchemaTable($connectionId);
        $childClasses = [];
        foreach (self::$schemaTableCache[$connectionId] as $class => $row) {
            if (in_array($abstractClass, $row['parents'])) {
                $childClasses[] = $class;
            }
        }
        $arr = [];
        foreach ($childClasses as $childClass) {
            // @codeCoverageIgnoreStart
            // this is rare case where hierarchy may be changed but db schema table is not yet updated
            // this is expected to never happen in production nor in development
            // after updating code that affect database, always a databaseupdate is required
            if (!class_exists($childClass)) {
                continue;
            }
            // @codeCoverageIgnoreEnd
            $arr = ArrayUtils::merge($arr, call_user_func_array([$childClass, $method], $parameters));
        }
        return $arr;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connectionId = self::getConnectionId();
    }

    /**
     * On clone
     */
    public function __clone(): void
    {
        throw new Exception('Native clone isnt supported - Use ->clone() on the storable', ErrorCode::NATIVE_CLONE);
    }

    /**
     * To string
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->id;
    }

    /**
     * Get property
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $label = "Property " . get_class($this) . "->$name";
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this, $name);
        if (!$storableSchemaProperty) {
            throw new Exception(
                "$label not exist in storable ",
                ErrorCode::STORABLE_PROPERTY_NOTEXIST
            );
        }
        // a complete new storable does not have anything set yet
        if (!$this->propertyCache) {
            return null;
        }
        if (ArrayUtils::keyExists($this->propertyCache, "phpvalue[$name]")) {
            return $this->propertyCache["phpvalue"][$name];
        }
        // lazy load if required
        if ($storableSchemaProperty->lazyFetch && $this->id && !ArrayUtils::keyExists(
                $this->propertyCache,
                "dbvalue[$name]"
            )) {
            $db = $this->getDb();
            $this->propertyCache["dbvalue"][$name] = $db->fetchOne(
                "SELECT `$storableSchemaProperty->name`
                FROM `" . $this::class . "`
                WHERE id = $this"
            );
        }
        $realValue = $this->getConvertedDbValue($name, $this->propertyCache["dbvalue"][$name] ?? null);
        $this->propertyCache["phpvalue"][$name] = $realValue;
        return $realValue;
    }

    /**
     * Set property
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void
    {
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this, $name);
        $label = "Property " . get_class($this) . "->$name";
        // native property support
        if (!$storableSchemaProperty) {
            throw new Exception(
                "$label not exist in storable ",
                ErrorCode::STORABLE_PROPERTY_NOTEXIST
            );
        }
        // check for correct types
        if ($value !== null) {
            if ($storableSchemaProperty->storableClass && !($value instanceof $storableSchemaProperty->storableClass)) {
                throw new Exception(
                    "$label need to be an instance of " . $storableSchemaProperty->storableClass,
                    ErrorCode::NOT_INSTANCEOF
                );
            }
            if ($storableSchemaProperty->storableInterface && !($value instanceof $storableSchemaProperty->storableInterface)) {
                throw new Exception(
                    "$label need to be an instance of " . $storableSchemaProperty->storableInterface,
                    ErrorCode::NOT_INSTANCEOF
                );
            }
            if ($storableSchemaProperty->arrayType || $storableSchemaProperty->arrayStorableClass || $storableSchemaProperty->arrayStorableInterface) {
                if (!is_array($value)) {
                    throw new Exception("$label needs to be an array", ErrorCode::NOT_TYPEOF);
                }
                // a storable class array must be unique in values
                $valueUnique = array_unique($value);
                if ($storableSchemaProperty->arrayStorableClass && count($valueUnique) !== count($value)) {
                    throw new Exception(
                        "$label have duplicate values in array of storable references",
                        ErrorCode::STORABLE_ARRAY_DUPE_REFERENCES
                    );
                }
                foreach ($value as $key => $arrayValue) {
                    $arrayLabel = $label . "[$key]";
                    if ($storableSchemaProperty->arrayType) {
                        switch ($storableSchemaProperty->arrayType) {
                            case "bool":
                                if (!is_bool($arrayValue)) {
                                    throw new Exception(
                                        "$arrayLabel need to be a boolean value", ErrorCode::NOT_TYPEOF
                                    );
                                }
                                break;
                            case "int":
                                if (!is_int($arrayValue)) {
                                    throw new Exception(
                                        "$arrayLabel need to be a integer value", ErrorCode::NOT_TYPEOF
                                    );
                                }
                                break;
                            case "float":
                                if (!is_float($arrayValue)) {
                                    throw new Exception("$arrayLabel need to be a float value");
                                }
                                break;
                            case "string":
                                if (!is_string($arrayValue)) {
                                    throw new Exception("$arrayLabel need to be a string value", ErrorCode::NOT_TYPEOF);
                                }
                                break;
                        }
                    }
                    if ($storableSchemaProperty->arrayStorableClass) {
                        if (!($arrayValue instanceof $storableSchemaProperty->arrayStorableClass)) {
                            throw new Exception(
                                "$arrayLabel needs to be instance of $storableSchemaProperty->arrayStorableClass",
                                ErrorCode::NOT_INSTANCEOF
                            );
                        }
                    }
                    if ($storableSchemaProperty->arrayStorableInterface) {
                        if (!($arrayValue instanceof $storableSchemaProperty->arrayStorableInterface)) {
                            throw new Exception(
                                "$arrayLabel needs to be instance of $storableSchemaProperty->arrayStorableClass",
                                ErrorCode::NOT_INSTANCEOF
                            );
                        }
                    }
                }
            } else {
                switch ($storableSchemaProperty->internalType) {
                    case "bool":
                        if (!is_bool($value)) {
                            throw new Exception("$label need to be a boolean value", ErrorCode::NOT_TYPEOF);
                        }
                        break;
                    case "int":
                        if (!is_int($value)) {
                            throw new Exception("$label need to be a integer value", ErrorCode::NOT_TYPEOF);
                        }
                        break;
                    case "float":
                        if (!is_float($value)) {
                            throw new Exception("$label need to be a float value", ErrorCode::NOT_TYPEOF);
                        }
                        break;
                    case "string":
                        if (!is_string($value)) {
                            throw new Exception("$label need to be a string value", ErrorCode::NOT_TYPEOF);
                        }
                        break;
                }
            }
        }
        $this->propertyCache['phpvalue'][$name] = $value;
        $this->propertyCache['modified'][$name] = true;
    }

    /**
     * Clone self without an id
     * @return static
     */
    public function clone(): static
    {
        $obj = new static();
        $obj->connectionId = $this->connectionId;
        foreach (self::getStorableSchema($this)->properties as $propertyName => $property) {
            if ($propertyName === 'id') {
                continue;
            }
            $obj->{$property->name} = $this->{$property->name};
        }
        return $obj;
    }

    /**
     * Get database connection
     * @return Mysql
     */
    final public function getDb(): Mysql
    {
        return Mysql::get($this->connectionId);
    }

    /**
     * Get the original db value of given property
     * This is the value that has been originally fetched from database if object comes from database, even if you have changed the property in the meantime
     * For new storables, this returns always null
     * @param string $propertyName
     * @return string|null
     */
    final public function getOriginalDbValueForProperty(string $propertyName): ?string
    {
        return $this->propertyCache['dbvalue'][$propertyName] ?? null;
    }

    /**
     * Get the database value the is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the given property value
     * So if you have modified a value of a property after fetching from database, than this value is the modified one
     * @param string $propertyName
     * @return string|null Always string or null, as database will also always return string or null, no other type
     */
    final public function getNewDbValueForProperty(string $propertyName): ?string
    {
        $storableSchemaProperty = self::getStorableSchemaProperty($this, $propertyName);
        if (!$storableSchemaProperty) {
            return null;
        }
        $phpValue = $this->{$propertyName};
        if ($phpValue === null) {
            return null;
        }
        if ($storableSchemaProperty->arrayStorableClass) {
            return JsonUtils::encode(array_values($phpValue));
        }
        if ($storableSchemaProperty->arrayType) {
            return JsonUtils::encode($phpValue);
        }
        if ($storableSchemaProperty->arrayStorableInterface) {
            $arr = [];
            foreach ($phpValue as $value) {
                if ($value instanceof ObjectTransformable) {
                    $arr[] = $value->getDbValue();
                }
            }
            return JsonUtils::encode($arr);
        }
        if ($storableSchemaProperty->storableInterface) {
            if ($phpValue instanceof ObjectTransformable) {
                return $phpValue->getDbValue();
            }
        }
        return match ($storableSchemaProperty->internalType) {
            "bool" => $phpValue ? '1' : '0',
            "mixed" => JsonUtils::encode($phpValue),
            default => (string)$phpValue
        };
    }

    /**
     * Has a property been modified and not yet saved in database
     * @param string $propertyName
     * @return bool
     */
    final public function isPropertyModified(string $propertyName): bool
    {
        return $this->propertyCache['modified'][$propertyName] ?? false;
    }

    /**
     * Store into database
     */
    public function store(): void
    {
        $storableSchema = Storable::getStorableSchema($this);
        $storeValues = [];
        foreach ($storableSchema->properties as $propertyName => $storableSchemaProperty) {
            // skip untouched properties in edit mode
            if ($this->id && (!$this->isPropertyModified($propertyName) || !ArrayUtils::keyExists(
                        $this->propertyCache,
                        'phpvalue[' . $propertyName . ']'
                    ))) {
                continue;
            }
            $finalDatabaseValue = $this->getNewDbValueForProperty($propertyName);
            // optional check
            if (!$storableSchemaProperty->optional && $finalDatabaseValue === null && $propertyName !== 'id') {
                throw new Exception(
                    "Property " . get_class($this) . "->$propertyName is null and not optional",
                    ErrorCode::NOT_OPTIONAL
                );
            }

            $storeValues[$propertyName] = $finalDatabaseValue;
        }
        // nothing changed, nothing to from here
        if (!$storeValues) {
            return;
        }
        $class = get_class($this);
        $db = $this->getDb();
        // get next available id from database
        $existingId = $this->id;
        if (!$existingId) {
            self::fetchSchemaTable($this->connectionId);
            $storableClassId = self::$schemaTableCache[$this->connectionId][$class]['id'];
            $db->insert(StorableSchema::ID_TABLE, ['storableId' => $storableClassId]);
            $this->id = $db->getLastInsertId();
            $storeValues["id"] = (string)$this->id;
        }
        if (!$existingId) {
            $db->insert($storableSchema->tableName, $storeValues);
        } else {
            $db->update($storableSchema->tableName, $storeValues, "id = $existingId");
        }
        // unset all modified flags after stored in database
        unset($this->propertyCache['modified']);
        self::$dbCache[$this->connectionId][get_class($this)][$this->id] = $this;
        $this->onDatabaseUpdated();
        // create system event logs
        $logCategory = $existingId ? SystemEventLog::CATEGORY_STORABLE_UPDATED : SystemEventLog::CATEGORY_STORABLE_CREATED;
        if (!($this instanceof SystemEventLog) && Config::get('systemEventLog[' . $logCategory . ']')) {
            SystemEventLog::create(
                $logCategory,
                null,
                ['id' => $this->id, 'connectionId' => $db->id, 'info' => $this->getRawTextString()],
                $db->id
            );
        }
    }

    /**
     * Delete from database
     * @param bool $force Force deletion even if isDeletable() is false
     */
    public function delete(bool $force = false): void
    {
        if (!$this->id) {
            throw new Exception(
                "Cannot delete new storable that is not yet saved in database",
                ErrorCode::STORABLE_NEW_DELETE
            );
        }
        if (!$force && !$this->isDeletable()) {
            throw new Exception(
                "Storable #" . $this . " (" . $this->getRawTextString() . ") is not deletable",
                ErrorCode::STORABLE_NOT_DELETABLE
            );
        }
        $db = $this->getDb();
        $storableSchema = Storable::getStorableSchema($this);
        $db->delete($storableSchema->tableName, "id = " . $this->id);
        $db->delete(StorableSchema::ID_TABLE, "id = " . $this->id);
        $id = $this->id;
        $textString = $this->getRawTextString();
        $this->id = null;
        unset(self::$dbCache[$this->connectionId][$this->id]);
        $this->onDatabaseUpdated();
        // create system event logs
        $logCategory = SystemEventLog::CATEGORY_STORABLE_DELETED;
        if (!($this instanceof SystemEventLog) && Config::get('systemEventLog[' . $logCategory . ']')) {
            SystemEventLog::create(
                $logCategory,
                null,
                ['id' => $id, 'connectionId' => $db->id, 'info' => $textString],
                $db->id
            );
        }
    }

    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return int|null
     */
    public function getDbValue(): ?int
    {
        return $this->id;
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        return $this . "|" . get_class($this);
    }

    /**
     * Get a value that is explicitely used when displayed inside a html table
     * @return mixed
     */
    public function getHtmlTableValue(): mixed
    {
        return trim($this->getHtmlString());
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this . "|" . get_class($this);
    }

    /**
     * Get a value that can be used in sort functions
     * @return string
     */
    public function getSortableValue(): string
    {
        return $this->getRawTextString();
    }

    /**
     * Json serialize
     * @return int|null
     */
    public function jsonSerialize(): ?int
    {
        return $this->id;
    }

    /**
     * Is this storable editable
     * @return bool
     */
    public function isEditable(): bool
    {
        return true;
    }

    /**
     * Is this storable deletable
     * @return bool
     */
    public function isDeletable(): bool
    {
        return false;
    }

    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        return null;
    }

    /**
     * This function is called when the database has been updated after a store() or delete() call
     */
    protected function onDatabaseUpdated(): void
    {
    }

    /**
     * Get the converted value that takes original database value and converts it to the final type
     * @param string $propertyName
     * @param mixed $dbValue
     * @return mixed
     */
    private function getConvertedDbValue(string $propertyName, mixed $dbValue): mixed
    {
        if ($dbValue === null) {
            return null;
        }
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this, $propertyName);
        $referenceStorableConnectionId = $storableSchemaProperty->connectionId === "_parent" ? $this->connectionId : $storableSchemaProperty->connectionId;
        $prefetchEnabled = self::$prefetchEnabled && $storableSchemaProperty->prefetchReferenceStorable;
        if ($prefetchEnabled) {
            /** @var Storable[] $cachedStorables */
            $cachedStorables = self::$dbCache[$this->connectionId][get_class($this)] ?? null;
        }
        if ($storableSchemaProperty->arrayType || $storableSchemaProperty->arrayStorableClass || $storableSchemaProperty->arrayStorableInterface) {
            // handling typed arrays
            $phpValue = JsonUtils::decode($dbValue);
            if ($storableSchemaProperty->arrayStorableClass) {
                if ($prefetchEnabled && $cachedStorables) {
                    // prefetch, loading all already cached storables up to the prefetchLimit
                    $fetchReferenceIds = [];
                    $count = 0;
                    $cachedJsonValues = [];
                    foreach ($cachedStorables as $cachedStorable) {
                        // if this was already in a previous prefetch cycle
                        if (ArrayUtils::keyExists($cachedStorable->propertyCache, "phpvalue[$propertyName]")) {
                            continue;
                        }
                        $referenceJsonStr = $cachedStorable->getOriginalDbValueForProperty($propertyName);
                        $cachedJsonValues[$cachedStorable->id] = null;
                        if ($referenceJsonStr) {
                            $referenceJsonData = JsonUtils::decode($referenceJsonStr);
                            $cachedJsonValues[$cachedStorable->id] = $referenceJsonData;
                            foreach ($referenceJsonData as $referenceId) {
                                $fetchReferenceIds[$referenceId] = $referenceId;
                            }
                            $count++;
                            if ($count >= $storableSchemaProperty->prefetchLimit) {
                                break;
                            }
                        }
                    }
                    $fetchReferenceIds[$this->id] = $this->id;
                    $referenceStorables = call_user_func_array(
                        [$storableSchemaProperty->arrayStorableClass, "getByIds"],
                        [$fetchReferenceIds, $referenceStorableConnectionId]
                    );
                    foreach ($cachedStorables as $cachedStorable) {
                        // not qualified for prefetch (limit reached, so skip)
                        if (!ArrayUtils::keyExists($cachedJsonValues, $cachedStorable->id)) {
                            continue;
                        }
                        $referenceJsonData = $cachedJsonValues[$cachedStorable->id];
                        $newValue = [];
                        if ($referenceJsonData) {
                            foreach ($referenceJsonData as $referenceId) {
                                if (isset($referenceStorables[$referenceId])) {
                                    $newValue[$referenceId] = $referenceStorables[$referenceId];
                                }
                            }
                        }
                        $cachedStorable->propertyCache['phpvalue'][$propertyName] = $newValue ?: null;
                    }
                } elseif (!$prefetchEnabled) {
                    // no prefetch, fetching directly with getByIds
                    return call_user_func_array(
                        [$storableSchemaProperty->arrayStorableClass, "getByIds"],
                        [$phpValue, $referenceStorableConnectionId]
                    );
                }
                return $this->propertyCache['phpvalue'][$propertyName] ?? null;
            } elseif ($storableSchemaProperty->arrayStorableInterface) {
                foreach ($phpValue as $key => $value) {
                    $phpValue[$key] = call_user_func_array([
                        $storableSchemaProperty->arrayStorableInterface,
                        'createFromDbValue'
                    ], [$value]);
                }
                return $phpValue;
            }
            return $phpValue;
        } elseif ($storableSchemaProperty->storableInterface) {
            return call_user_func_array([$storableSchemaProperty->storableInterface, 'createFromDbValue'], [$dbValue]);
        } elseif ($storableSchemaProperty->storableClass) {
            if ($prefetchEnabled) {
                if ($cachedStorables) {
                    $fetchReferenceIds = [];
                    $count = 0;
                    foreach ($cachedStorables as $cachedStorable) {
                        $referenceId = $cachedStorable->getOriginalDbValueForProperty($propertyName);
                        // if this was already in a previous prefetch cycle
                        if (ArrayUtils::keyExists($cachedStorable->propertyCache, "phpvalue[$propertyName]")) {
                            continue;
                        }
                        if ($referenceId) {
                            $fetchReferenceIds[$referenceId] = $referenceId;
                            $count++;
                            if ($count >= $storableSchemaProperty->prefetchLimit) {
                                break;
                            }
                        }
                    }
                    $referenceStorables = call_user_func_array(
                        [$storableSchemaProperty->storableClass, "getByIds"],
                        [$fetchReferenceIds, $referenceStorableConnectionId]
                    );
                    foreach ($cachedStorables as $cachedStorable) {
                        $referenceId = $cachedStorable->getOriginalDbValueForProperty($propertyName);
                        if ($referenceId && isset($fetchReferenceIds[$referenceId])) {
                            $cachedStorable->propertyCache['phpvalue'][$propertyName] = $referenceStorables[$referenceId] ?? null;
                        }
                    }
                }
                return $this->propertyCache['phpvalue'][$propertyName] ?? null;
            }
            return call_user_func_array(
                [$storableSchemaProperty->storableClass, "getById"],
                [$dbValue, $referenceStorableConnectionId]
            );
        } else {
            return match ($storableSchemaProperty->internalType) {
                "bool" => (bool)$dbValue,
                "int" => (int)$dbValue,
                "float" => (float)$dbValue,
                "mixed" => JsonUtils::decode($dbValue),
                default => $dbValue
            };
        }
    }
}