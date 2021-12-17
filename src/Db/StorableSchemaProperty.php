<?php

namespace Framelix\Framelix\Db;

/**
 * Storable schema property
 */
class StorableSchemaProperty
{
    /**
     * The properties name
     * @var string
     */
    public string $name;

    /**
     * Is property optional - Is checked when calling store()
     * @var bool
     */
    public bool $optional = false;

    /**
     * Is column auto increment in database
     * @var bool
     */
    public bool $autoIncrement = false;

    /**
     * Internal php type that this property finally is
     * Which is any scalar type like bool, int, etc... or a class name
     * @var string|null
     */
    public ?string $internalType = null;

    /**
     * The storable class if the property is a reference to another storable class
     * @var string|null
     */
    public ?string $storableClass = null;

    /**
     * The storable interface class if the property is a reference to a storable interface
     * @var string|null
     */
    public ?string $storableInterface = null;

    /**
     * The database type for mysql
     * @var string|null
     */
    public ?string $databaseType = null;

    /**
     * Length restriction
     * @var int|null
     */
    public ?int $length = null;

    /**
     * Decimals when using floating numbers
     * @var int|null
     */
    public ?int $decimals = null;

    /**
     * Allow null
     * By default, everything can be null
     * @var bool
     */
    public bool $allowNull = true;

    /**
     * Is unsigned number
     * @var bool|null
     */
    public ?bool $unsigned = null;

    /**
     * A comment for the database column, directly in the database
     * @var string|null
     */
    public ?string $dbComment = null;

    /**
     * Lazy fetch does not fetch this property from database until it is actually called
     * Useful for blobs and big data chunks that you don't need often
     * @var bool
     */
    public bool $lazyFetch = false;

    /**
     * This is set when type is declared as array (eg: Storable[]) and does contain the type
     * @var string|null
     */
    public string|null $arrayType = null;

    /**
     * This is set when type is declared as array (eg: Storable[]) that is a storable
     * @var string|null
     */
    public string|null $arrayStorableClass = null;

    /**
     * This is set when type is declared as array (eg: DateTime[]) that is a storable interface
     * @var string|null
     */
    public string|null $arrayStorableInterface = null;

    /**
     * Comes after this property in the database
     * @var StorableSchemaProperty|null
     */
    public ?StorableSchemaProperty $after = null;

    /**
     * The database connection id to use in case this is a storableClass property
     * This will override any default behaviour
     * The special value "_parent" will use the dbid of the parent
     * @var string
     */
    public string $connectionId = "_parent";

    /**
     * If true then prefetch the reference for the same property of every already fetched storable that exist in the cache
     * But can also stress the database with thousands of objects
     * The storable getter will automatically prefetch the same property for every alredy fetched storable
     * Will help to greatly reduce databaes queries
     * Even with 10.000+ objects at once
     * Deactivate if you not want it, as it can negative performance impact with large storable object sets
     * In practice this is an improvement almost everytime, so deactivating should not be necessary
     * @var bool
     */
    public bool $prefetchReferenceStorable = true;

    /**
     * If prefetch is enabled for this property, limit the amount of prefetched objects
     * @var int
     */
    public int $prefetchLimit = 1000;
}