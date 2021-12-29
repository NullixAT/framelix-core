<?php

namespace Framelix\Framelix\Db;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\PhpDocParser;
use JetBrains\PhpStorm\ExpectedValues;
use ReflectionClass;

use function array_combine;
use function array_key_last;
use function call_user_func_array;
use function explode;
use function in_array;
use function preg_match;
use function str_contains;
use function str_ends_with;
use function strtolower;
use function substr;

/**
 * Storable schema
 */
class StorableSchema
{
    public const ID_TABLE = "framelix__id";
    public const SCHEMA_TABLE = "framelix__schema";

    /**
     * The parent storable schemas
     * @var StorableSchema[]
     */
    public array $parentStorableSchemas = [];

    /**
     * The table name
     * @var string
     */
    public string $tableName;

    /**
     * The properties
     * @var StorableSchemaProperty[]
     */
    public array $properties = [];

    /**
     * The indexes
     * @var array
     */
    public array $indexes = [];

    /**
     * Is this class abstract
     * @var bool
     */
    public bool $abstract = false;

    /**
     * The database connection id to use
     * This will override any default behaviour
     * @var string|null
     */
    public ?string $connectionId = null;

    /**
     * Constructor
     * @param string $className
     */
    public function __construct(public string $className)
    {
        $this->tableName = strtolower(trim(str_replace("\\", "_", $className), "_"));
    }

    /**
     * Merge another schemas properties and indexes into this
     * @param StorableSchema $storableSchema
     */
    public function mergeParent(StorableSchema $storableSchema): void
    {
        $this->properties = ArrayUtils::merge($storableSchema->properties, $this->properties);
        $this->indexes = ArrayUtils::merge($storableSchema->indexes, $this->indexes);
        $this->parentStorableSchemas[$storableSchema->className] = $storableSchema;
    }

    /**
     * Parse properties and other defination from doc comment of the class
     */
    public function parseClassData(): void
    {
        $reflectionClass = new ReflectionClass($this->className);
        $this->abstract = $reflectionClass->isAbstract();
        $uses = [];
        $file = fopen($reflectionClass->getFileName(), "r");
        while ($line = fgets($file)) {
            if (preg_match("~^use (.*?);~", $line, $match)) {
                $uses[] = $match[1];
            }
            if (preg_match("~^abstract class|^class~i", $line)) {
                break;
            }
        }
        fclose($file);

        $namespace = $reflectionClass->getNamespaceName();
        $parsedComment = PhpDocParser::parseVariableDescriptions($reflectionClass->getDocComment(), 'property');
        foreach ($parsedComment as $propertyName => $commentData) {
            if ($commentData['type'] === null) {
                throw new Exception(
                    "No valid @property annotation (Missing type) in " . $reflectionClass->getName(),
                    ErrorCode::STORABLESCHEMA_INVALID_PROPERTY_TYPE
                );
            }
            $possibleClassNames = [];
            $types = explode("|", trim($commentData['type']));
            $storableSchemaProperty = $this->createProperty($propertyName);
            if ($propertyName === 'id') {
                $storableSchemaProperty->databaseType = 'bigint';
                $storableSchemaProperty->length = 18;
                $storableSchemaProperty->allowNull = false;
            }
            $types = array_combine($types, $types);
            $storableSchemaProperty->optional = $types['null'] ?? false;
            unset($types['null']);
            $type = reset($types);
            $typeIsArray = str_ends_with($type, "[]");
            // typed array
            if ($typeIsArray) {
                $type = substr($type, 0, -2);
                $storableSchemaProperty->arrayType = $type;
            }
            if (!in_array($type, ["bool", "int", "float", "double", "string", "mixed"])) {
                $possibleClassNames[] = $namespace . "\\" . $type;
                if ($uses) {
                    $classFirstSep = strpos($type, "\\");
                    $classFirstPart = $type;
                    if ($classFirstSep !== false) {
                        $classFirstPart = substr($type, 0, $classFirstSep);
                    }
                    foreach ($uses as $use) {
                        if (str_contains($use, "\\")) {
                            if (str_ends_with($use, "\\" . $classFirstPart)) {
                                $possibleClassNames[] = $use . substr(
                                        $type,
                                        strlen($classFirstPart)
                                    );
                            }
                        }
                    }
                }
            }

            foreach ($possibleClassNames as $possibleClassName) {
                $expl = explode("\\", $possibleClassName, 3);
                $relativeClassName = $expl[2];
                $classFiles = [
                    str_replace(
                        "\\",
                        "/",
                        FileUtils::getModuleRootPath($expl[1]) . "/src/" . $relativeClassName . ".php"
                    )
                ];
                foreach ($classFiles as $classFile) {
                    if (file_exists($classFile)) {
                        $isStorable = str_contains($classFile, "/src/Storable/");
                        $type = $possibleClassName;
                        if ($storableSchemaProperty->arrayType) {
                            // typed storable array or typed storable interface array
                            $storableSchemaProperty->arrayType = null;
                            if ($isStorable) {
                                $storableSchemaProperty->arrayStorableClass = $possibleClassName;
                            } else {
                                $storableSchemaProperty->arrayStorableInterface = $possibleClassName;
                            }
                            $type = "mixed";
                        } elseif (!$isStorable) {
                            // storable interfaces
                            $storableSchemaProperty->storableInterface = $possibleClassName;
                            call_user_func_array(
                                [$possibleClassName, 'setupSelfStorableSchemaProperty'],
                                [$storableSchemaProperty]
                            );
                        } else {
                            // storable classes
                            $storableSchemaProperty->storableClass = $possibleClassName;
                            $storableSchemaProperty->databaseType = "bigint";
                            $storableSchemaProperty->length = 18;
                            $storableSchemaProperty->unsigned = true;
                            $this->addIndex($propertyName, 'index');
                        }
                        break 2;
                    }
                }
            }
            // if not found as suitable class for the array, than it is a native php typed array
            if ($storableSchemaProperty->arrayType) {
                $type = "mixed";
            }
            // if type is not set with defaults above, so check for default php types now
            if (!$storableSchemaProperty->databaseType) {
                if ($type === 'bool') {
                    // booleans are automatically set to tinyint
                    $storableSchemaProperty->databaseType = 'tinyint';
                    $storableSchemaProperty->length = 1;
                }
                if ($type === 'int') {
                    // int is considered to be 11 long
                    $storableSchemaProperty->databaseType = 'int';
                    $storableSchemaProperty->length = 11;
                }
                if ($type === 'double') {
                    throw new Exception(
                        "Double is considered deprecated in php - Use float instead",
                        ErrorCode::STORABLESCHEMA_INVALID_DOUBLE
                    );
                }
                if ($type === 'float') {
                    // float in PHP will be double in mysql for higher precision
                    $storableSchemaProperty->databaseType = "double";
                }
                if ($type === 'string') {
                    // string is considered to be varchar with length of max varchar length that can be indexed
                    $storableSchemaProperty->databaseType = 'varchar';
                    $storableSchemaProperty->length = 191;
                }
                if ($type === 'mixed') {
                    // mixed is considered to be json of any length, so reserve big space
                    $storableSchemaProperty->databaseType = 'longtext';
                }
            }
            $storableSchemaProperty->internalType = $type;
            $this->properties[$propertyName] = $storableSchemaProperty;
        }
    }

    /**
     * Create a property with given name and attach it to this schema
     * @param string $name
     * @return StorableSchemaProperty
     */
    public function createProperty(string $name): StorableSchemaProperty
    {
        $lastPropertyName = array_key_last($this->properties);
        $storableSchemaProperty = new StorableSchemaProperty();
        $storableSchemaProperty->name = $name;
        $storableSchemaProperty->after = $lastPropertyName ? $this->properties[$lastPropertyName] : null;
        $this->properties[$name] = $storableSchemaProperty;
        return $storableSchemaProperty;
    }

    /**
     * Add an index
     * @param string $indexName If $properties is not set, then the name must equal property name
     * @param string $type
     * @param array|null $properties If not set than use $indexName as the only property to add
     */
    public function addIndex(
        string $indexName,
        #[ExpectedValues(["unique", "index", "fulltext"])]
        string $type,
        ?array $properties = null
    ): void {
        if (!$properties) {
            $properties = [$indexName];
        }
        $this->indexes[$indexName] = [
            'type' => strtolower($type),
            'properties' => $properties
        ];
    }
}