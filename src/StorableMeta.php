<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\QuickSearch;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\SystemValue;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\StringUtils;
use JsonSerializable;

use function base64_decode;
use function base64_encode;
use function get_class;
use function is_array;
use function mb_strtolower;
use function strlen;
use function trim;

/**
 * Storable meta
 */
abstract class StorableMeta implements JsonSerializable
{
    public const CONTEXT_TABLE = 1;
    public const CONTEXT_FORM = 2;
    public const CONTEXT_QUICK_SEARCH = 3;
    public const CONTEXT_EXTENDED_SEARCH = 4;

    /**
     * The meta id
     * Default is the storables class name
     * @var string
     */
    public string $id;

    /**
     * Current context
     * @var int
     */
    public int $context;

    /**
     * Storable properties
     * @var StorableMetaProperty[]
     */
    public array $properties = [];

    /**
     * This table is used as the base for getTable()
     * @var Table
     */
    public Table $tableDefault;

    /**
     * This form is used as the base for getEditForm()
     * @var Form
     */
    public Form $editFormDefault;

    /**
     * This form is used as the base for getExtendedSearchForm()
     * @var Form
     */
    public Form $extendedSearchFormDefault;

    /**
     * This condition base is used for all search methods the user lazy search
     * Set fixed conditions or add custom columns, to limit the results or search in specific columns, for example
     * @var LazySearchCondition
     */
    public LazySearchCondition $lazySearchConditionDefault;

    /**
     * Optional parameters which can be used in init() to control the init behaviour
     * This is the only variable that is passed through ajax calls (like search request and so on)
     * @var array|null
     */
    public ?array $parameters = null;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'savesort':
                $ids = $jsCall->parameters['ids'] ?? null;
                if (is_array($ids) && $ids) {
                    $firstRow = reset($ids);
                    $objects = Storable::getByIds(
                        ArrayUtils::map($ids, "id"),
                        $firstRow['connection-id']
                    );
                    $sort = 0;
                    foreach ($objects as $object) {
                        if (!Storable::getStorableSchemaProperty($object, "sort")) {
                            throw new Exception(
                                'Missing "sort" property on ' . get_class($object),
                                ErrorCode::STORABLE_SORT_CONDITION
                            );
                        }
                        $object->{"sort"} = $sort++;
                        if ($object instanceof StorableExtended) {
                            $object->preserveUpdateUserAndTime();
                        }
                        $object->store();
                    }
                    $jsCall->result = true;
                }
                break;
            case 'quicksearch':
                $meta = StorableMeta::createFromUrl(Url::create());
                $query = trim((string)($jsCall->parameters['query'] ?? null));
                if (strlen($query)) {
                    if (User::hasRole('dev')) {
                        $condition = $meta->getQuickSearchCondition($jsCall->parameters['options'] ?? null);
                        Response::header(
                            "x-debug-query: " . $condition->getPreparedCondition($meta->storable->getDb(), $query)
                        );
                    }
                    $objects = $meta->getQuickSearchResult($query, $jsCall->parameters['options'] ?? null);
                    if ($objects) {
                        $meta->getTable($objects, "quicksearch")->show();
                    } else {
                        echo '<div class="framelix-alert">' . Lang::get('__framelix_form_search_noresult__') . '</div>';
                    }
                } else {
                    $jsCall->result = '';
                }
                break;
        }
    }


    /**
     * Recreate instance from a url
     * @param Url $url
     * @return StorableMeta
     */
    public static function createFromUrl(Url $url): StorableMeta
    {
        $metaClass = $url->getParameter('metaClass');
        $storableClass = $url->getParameter('storableClass');
        $parameters = $url->getParameter('metaParameters');
        ClassUtils::validateClassName($metaClass);
        ClassUtils::validateClassName($storableClass);
        /** @var Storable $storable */
        $storable = new $storableClass();
        /** @var StorableMeta $meta */
        $meta = new $metaClass($storable);
        $meta->parameters = JsonUtils::decode(base64_decode($parameters));
        return $meta;
    }

    /**
     * Constructor
     * @param Storable $storable The storable assigned to the meta, this may change depending on context
     */
    public function __construct(public Storable $storable)
    {
        $this->id = mb_strtolower(StringUtils::slugify(get_class($storable)));
        $this->tableDefault = new Table();
        $this->editFormDefault = new Form();
        $this->extendedSearchFormDefault = new Form();
        $this->lazySearchConditionDefault = new LazySearchCondition();
    }

    /**
     * Get the quick search instance
     * @return QuickSearch
     */
    public function getQuickSearch(): QuickSearch
    {
        $quickSearch = new QuickSearch();
        $quickSearch->id = "quicksearch-" . $this->id;
        $quickSearch->setSearchMethod(__CLASS__, "quicksearch", $this->jsonSerialize());
        $lazyCondition = $this->getQuickSearchCondition();
        if ($lazyCondition->columns) {
            foreach ($lazyCondition->columns as $row) {
                $quickSearch->addColumn($row['frontendPropertyName'], $row['label'], $row['type']);
            }
        }
        return $quickSearch;
    }

    /**
     * Get the quick search lazy condition
     * @param array|null $options Option values set by the user in the interface
     * @return LazySearchCondition
     */
    public function getQuickSearchCondition(array $options = null): LazySearchCondition
    {
        $this->initContext(self::CONTEXT_QUICK_SEARCH);
        $lazyCondition = clone $this->lazySearchConditionDefault;
        foreach ($this->properties as $storableMetaProperty) {
            if (!$storableMetaProperty->getVisibility()) {
                continue;
            }
            $schemaProperty = $storableMetaProperty->getSchemaProperty();
            if (!$schemaProperty) {
                continue;
            }
            $name = $schemaProperty->name;
            if ($storableMetaProperty->lazySearchConditionColumns->columns) {
                foreach ($storableMetaProperty->lazySearchConditionColumns->columns as $key => $row) {
                    $row['label'] = $row['label'] ?? $storableMetaProperty->getLabel();
                    $lazyCondition->columns[$key] = $row;
                }
            } else {
                $lazyCondition->addColumn(
                    "`t0`.`$name`",
                    $name,
                    $storableMetaProperty->getLabel(),
                    $schemaProperty->internalType
                );
            }
        }
        return $lazyCondition;
    }

    /**
     * Get array of all storables matched by the quick search query
     * @param string $query
     * @param array|null $options Option values set by the user in the interface
     * @return Storable[]
     */
    public function getQuickSearchResult(string $query, array $options = null): array
    {
        return $this->storable::getByCondition(
            $this->getQuickSearchCondition($options)->getPreparedCondition(
                $this->storable->getDb(),
                $query
            )
        );
    }

    /**
     * Create a property, add it the storablemeta and return the property
     * @param string $name
     * @return StorableMetaProperty
     */
    public function createProperty(
        string $name
    ): StorableMetaProperty {
        $storableSchemaProperty = Storable::getStorableSchemaProperty($this->storable, $name);
        $property = new StorableMetaProperty();
        $property->meta = $this;
        $property->name = $name;
        $langKeyLabel = ClassUtils::getLangKey($this->storable, $name . "_label");
        $langKeyDesc = ClassUtils::getLangKey($this->storable, $name . "_label_desc");
        // get better labels for some built in storables like system value
        if ($storableSchemaProperty->storableClass ?? null) {
            $storableClass = new $storableSchemaProperty->storableClass();
            if ($storableClass instanceof SystemValue) {
                $langKeyLabel = ClassUtils::getLangKey(
                    str_replace(
                        "\\Storable\\SystemValue\\",
                        "\\View\\Backend\\SystemValue\\",
                        $storableSchemaProperty->storableClass
                    )
                );
            }
        }
        $property->setLabel($langKeyLabel);
        if (Lang::keyExist($langKeyDesc)) {
            $property->setLabelDescription($langKeyDesc);
        }
        $this->properties[$property->name] = $property;
        return $property;
    }

    /**
     * Add a property that display the storable modification timestamp properly formatted
     * Does only work for storables that inherit from StorableExtended
     * @return StorableMetaProperty|null
     */
    public function addTimestampProperty(): ?StorableMetaProperty
    {
        if (!($this->storable instanceof StorableExtended)) {
            return null;
        }
        $this->tableDefault->addColumnFlag('timestamp', Table::COLUMNFLAG_SMALLFONT);
        $this->tableDefault->addColumnFlag('timestamp', Table::COLUMNFLAG_SMALLWIDTH);
        $property = $this->createProperty("timestamp");
        $property->setLabel("__framelix_modified_timestamp__");
        $property->setVisibility(null, false);
        $property->setVisibility(self::CONTEXT_TABLE, true);
        $property->valueCallable = function () {
            if ($this->storable instanceof StorableExtended) {
                return $this->storable->getModifiedTimestampTableCell();
            }
            return null;
        };
        return $property;
    }

    /**
     * (Re)Initialize meta with given context
     * @param int $context
     */
    public function initContext(
        int $context
    ): void {
        $this->context = $context;
        $this->properties = [];
        $this->init();
    }

    /**
     * Get edit form id
     * @return string
     */
    public function getEditFormId(): string
    {
        return "metaform-" . $this->id;
    }

    /**
     * Get form to edit the metas storable
     * @return Form
     */
    public function getEditForm(): Form
    {
        $this->initContext(self::CONTEXT_FORM);

        $form = clone $this->editFormDefault;
        $form->id = $this->getEditFormId();
        $form->getHtmlAttributes()->set('data-storable-id', $this->storable->id);
        foreach ($this->properties as $storableMetaProperty) {
            if (!$storableMetaProperty->getVisibility()) {
                continue;
            }
            $field = $storableMetaProperty->getField();
            if ($field) {
                $form->addField($field);
            }
        }
        if ($this->storable->isEditable()) {
            $form->addSubmitButton(
                "store",
                $this->storable->id ? "__framelix_save_edited__" : "__framelix_save_new__",
                $this->storable->id ? "save_as" : "save"
            );
        }
        if (Url::getBrowserUrl()->hasParameterWithValue($this->storable)) {
            $form->addLoadUrlButton(
                Url::getBrowserUrl()->removeParameterByValue($this->storable),
                "__framelix_stop_edit__",
                "close",
                buttonTooltip: '__framelix_stop_edit_tooltip__'
            );
        }
        return $form;
    }


    /**
     * Get html table filled with rows for the given storable objects
     * @param Storable[] $objects
     * @param string|null $idAffix To separate multiple html tables if you use it many times
     * @return Table
     */
    public function getTable(
        array $objects,
        ?string $idAffix = null
    ): Table {
        $this->initContext(self::CONTEXT_TABLE);

        $table = clone $this->tableDefault;
        $table->id = "table-" . $this->id . ($idAffix !== null ? "-" . $idAffix : null);

        $properties = $this->properties;
        foreach ($properties as $key => $storableMetaProperty) {
            if (!$storableMetaProperty->getVisibility()) {
                unset($properties[$key]);
            }
        }

        $header = [];
        foreach ($properties as $property) {
            $storableSchemaProperty = $property->getSchemaProperty();
            $internalType = $storableSchemaProperty->internalType ?? null;
            $storableInterface = $storableSchemaProperty->storableInterface ?? null;
            $attr = clone $property->tableCellDefaultAttributes;
            $table->setCellHtmlAttributes(0, $property->name, $attr, "thead");
            if ($internalType === 'bool' || $internalType === 'float' || $internalType === 'int' || $storableInterface === Date::class || $storableInterface === DateTime::class) {
                $this->tableDefault->addColumnFlag($property->name, Table::COLUMNFLAG_SMALLWIDTH);
            }
            $header[$property->name] = $property->getLabel();
        }
        $table->setRowValues(0, $header, 'thead');

        $originalStorable = $this->storable;
        foreach ($objects as $object) {
            if (!$object instanceof $this->storable) {
                throw new Exception(
                    'Passed invalid storable with type ' . get_class($object) . ' to meta, expected ' . get_class(
                        $this->storable
                    ),
                    ErrorCode::STORABLEMETA_NOSTORABLE
                );
            }
            $this->storable = $object;
            $rowValues = [];
            foreach ($properties as $propertyName => $storableMetaProperty) {
                $value = $storableMetaProperty->getValue();
                $rowValues[$propertyName] = $value;
            }
            $rowKey = $table->createRow($rowValues);
            $table->setRowStorable($rowKey, $this->storable);
        }
        $this->storable = $originalStorable;
        return $table;
    }


    /**
     * Get html table filled with rows for the given storable objects
     * Also add mouse dragSort and the ability to store the sorting into the database
     * For this, the storable must have a $sort property in the database
     * @param Storable[] $objects
     * @param string|null $idAffix To separate multiple html tables if you use it many times
     * @return Table
     */
    public function getTableWithStorableSorting(
        array $objects,
        ?string $idAffix = null
    ): Table {
        $table = $this->getTable($objects, $idAffix);
        $table->dragSort = true;
        $table->appendHtml = '<script>FramelixStorableMeta.enableStorableSorting("' . $table->id . '", "' . JsCall::getCallUrl(
                __CLASS__,
                "savesort"
            ) . '")</script>';
        return $table;
    }

    /**
     * Show search and table in a tab container
     * @param Storable[] $objects
     */
    public function showSearchAndTableInTabs(array $objects): void
    {
        $tabs = new Tabs("tabs-" . $this->id);

        Buffer::start();
        $table = $this->getTable($objects);
        $table->show();
        $tabs->addTab(
            'entries',
            Lang::get('__framelix_meta_searchandtable_entries__') . " (" . count($objects) . ")",
            Buffer::get()
        );

        Buffer::start();
        $quickSearch = $this->getQuickSearch();
        $quickSearch->show();
        $tabs->addTab('search', "__framelix_meta_searchandtable_search__", Buffer::get());

        $tabs->show();
    }

    /**
     * Json serialize
     */
    public function jsonSerialize(): array
    {
        return [
            'metaClass' => get_class($this),
            'storableClass' => get_class($this->storable),
            'metaParameters' => base64_encode(JsonUtils::encode($this->parameters))
        ];
    }

    /**
     * Set default properties at the start
     */
    protected function addDefaultPropertiesAtStart(): void
    {
        $this->tableDefault->addColumnFlag('id', Table::COLUMNFLAG_SMALLWIDTH);
        $this->tableDefault->addColumnFlag('id', Table::COLUMNFLAG_SMALLFONT);
        $property = $this->createProperty("id");
        $property->setLabel("ID");
    }

    /**
     * Set default properties at the end
     */
    protected function addDefaultPropertiesAtEnd(): void
    {
        $this->addTimestampProperty();
    }

    /**
     * Initialize this meta
     */
    abstract protected function init(): void;
}