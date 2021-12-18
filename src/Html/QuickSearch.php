<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use JsonSerializable;

/**
 * Quick search interface
 */
class QuickSearch implements JsonSerializable
{
    /**
     * Id for the table
     * Default is random generated in constructor
     * @var string
     */
    public string $id;

    /**
     * Placeholder fpr the search input
     * @var string
     */
    public string $placeholder = '__framelix_quick_search_placeholder__';

    /**
     * Remember last search
     * @var bool
     */
    public bool $rememberSearch = true;

    /**
     * Automatically start search when quick search is loaded and last search data exists
     * @var bool
     */
    public bool $autostartSearch = true;

    /**
     * Assigned table
     * If set then load results into this table container of an own result container
     * @var Table|null
     */
    public ?Table $assignedTable = null;

    /**
     * Search method
     * @var array
     */
    private array $searchMethod = [];

    /**
     * Option fields
     * @var Field[]
     */
    private array $optionFields = [];

    /**
     * Available columns
     * @var array
     */
    public array $columns = [];


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = RandomGenerator::getRandomHtmlId();
    }

    /**
     * Add hidden option field
     * @param string $optionName
     * @param string $label
     * @param mixed $value
     */
    public function addOptionHidden(string $optionName, string $label, mixed $value): void
    {
        $field = new Field\Hidden();
        $field->name = $optionName;
        $field->label = $label;
        $field->defaultValue = $value;
        $this->addOptionField($field);
    }

    /**
     * Add toggle option field
     * @param string $optionName
     * @param string $label
     * @param bool $defaultValue
     */
    public function addOptionToggle(string $optionName, string $label, bool $defaultValue = false): void
    {
        $field = new Field\Toggle();
        $field->name = $optionName;
        $field->label = $label;
        $field->defaultValue = $defaultValue;
        $this->addOptionField($field);
    }

    /**
     * Add option field
     * @param Field $field
     */
    public function addOptionField(Field $field): void
    {
        $this->optionFields[$field->name] = $field;
    }

    /**
     * Add option fields
     * @param Field[] $fields
     */
    public function addOptionFields(array $fields): void
    {
        foreach ($fields as $field) {
            $this->addOptionField($field);
        }
    }

    /**
     *
     * Add a column to be able to search for
     * This will provide the user a form where it is possible to select specific column and comparison methods
     * @param string $frontendPropertyName The property that can be entered in the frontend by the user, to select the specific column for the query part
     *  This must be added with the same $frontendPropertyName as in the corresponding LazySearchCondition
     * @param string|null $label The label for the user to have a readable name
     * @param string $type The column type which is the internal php type (int,float,etc...) or a valid class name
     */
    public function addColumn(string $frontendPropertyName, ?string $label, string $type = "string"): void
    {
        $this->columns[$frontendPropertyName] = [
            'frontendPropertyName' => $frontendPropertyName,
            'label' => $label,
            'type' => $type,
        ];
    }

    /**
     * Set search method - Call will be done with FramelixApi.callPhpMethod()
     * @param string|null $callableMethod Could be class name only, then onJsCall is the method name
     * @param string $action The action
     * @param array|null $parameters Parameters to pass by
     */
    public function setSearchMethod(?string $callableMethod, string $action, ?array $parameters = null): void
    {
        $this->searchMethod = ['callableMethod' => $callableMethod, "action" => $action, "parameters" => $parameters];
    }

    /**
     * Show table
     */
    final public function show(): void
    {
        $jsonData = JsonUtils::encode($this);
        $randomId = RandomGenerator::getRandomHtmlId();
        ?>
        <div id="<?= $randomId ?>"></div>
        <script>
          (async function () {
            const instance = FramelixQuickSearch.createFromPhpData(<?=$jsonData?>)
            instance.render()
            $("#<?=$randomId?>").replaceWith(instance.container)
          })()
        </script>
        <?php
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $properties = [];
        foreach ($this as $key => $value) {
            $properties[$key] = $value;
        }
        $properties['signedUrlSearch'] = JsCall::getCallUrl(
            $this->searchMethod['callableMethod'],
            $this->searchMethod['action'],
            $this->searchMethod['parameters']
        );
        unset($properties['searchMethod']);
        if ($this->assignedTable) {
            $properties['assignedTable'] = $this->assignedTable->id;
        }
        return [
            'properties' => $properties
        ];
    }

}