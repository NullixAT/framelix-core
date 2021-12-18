<?php

namespace Framelix\Framelix\Html;

use Exception;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\ObjectTranformable;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Time;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\NumberUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\Utils\StringUtils;
use JsonSerializable;

use function array_combine;
use function array_keys;
use function array_unshift;
use function in_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function mb_strtolower;
use function trim;

/**
 * Table
 */
class Table implements JsonSerializable
{
    /**
     * No special behaviour
     * @var string
     */
    public const COLUMNFLAG_DEFAULT = 'default';

    /**
     * An icon column
     * @var string
     */
    public const COLUMNFLAG_ICON = 'icon';

    /**
     * Use smallest width possible
     * @var string
     */
    public const COLUMNFLAG_SMALLWIDTH = 'smallwidth';

    /**
     * Use a smaller font
     * @var string
     */
    public const COLUMNFLAG_SMALLFONT = 'smallfont';

    /**
     * Ignore sort for this column
     * @var string
     */
    public const COLUMNFLAG_IGNORESORT = 'ignoresort';

    /**
     * Ignore editurl click on this column
     * @var string
     */
    public const COLUMNFLAG_IGNOREURL = 'ignoreurl';

    /**
     * Remove the column if all cells in the tbody are empty
     * @var string
     */
    public const COLUMNFLAG_REMOVE_IF_EMPTY = 'removeifempty';
    /**
     * Id for the table
     * Default is random generated in constructor
     * @var string
     */
    public string $id;

    /**
     * The column order in which order the columns are displayed
     * Automatically set by first added row
     * @var array
     */
    public array $columnOrder = [];

    /**
     * If you want to sum columns in the footer, set the columns here
     * @var array|null
     */
    public ?array $footerSumColumns = null;

    /**
     * The rows internal data
     * Grouped by thead/tbody/tfoot
     * @var array
     */
    protected array $rows = [];
    /**
     * Is the table sortable
     * @var bool
     */
    public bool $sortable = true;

    /**
     * The initial sort
     * Array value is cellName prefixed with +/- (+ = ASC, - = DESC)
     * Example: ["+cellName", "-cellName"]
     * @var string[]
     */
    public ?array $initialSort = null;

    /**
     * Remember the sort settings in client based on the tables id
     * @var bool
     */
    public bool $rememberSort = true;

    /**
     * Add a checkbox column at the beginning
     * @var bool
     */
    public bool $checkboxColumn = false;

    /**
     * Add a column at the beginning, where the user can sort the table rows by drag/drop
     * @var bool
     */
    public bool $dragSort = false;

    /**
     * General flag if the generated table has edit urls or not
     * If true then it also depends on the storable getEditUrl return value
     * @var bool
     */
    public bool $storableEditable = true;

    /**
     * General flag if the generated table has deletable button for a storable row
     * If true then it also depends on the storable isDeletable return value
     * @var bool
     */
    public bool $storableDeletable = true;

    /**
     * If a row has an url attached, open in a new tab instead of current tab
     * @var bool
     */
    public bool $urlOpenInNewTab = false;

    /**
     * Column flags
     * @var array
     */
    public array $columnFlags = [];

    /**
     * Include some html before <table>
     * @var string|null
     */
    public ?string $prependHtml = null;

    /**
     * Include some html before <table>
     * @var string|null
     */
    public ?string $appendHtml = null;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'deleteStorable':
                try {
                    $storable = Storable::getById(Request::getGet('id'), Request::getGet('connectionId'));
                    $storable?->delete();
                    $jsCall->result = true;
                } catch (Exception $e) {
                    $jsCall->result = $e->getMessage();
                }
                break;
        }
    }

    /**
     * Show table
     */
    final public function show(): void
    {
        $jsonData = JsonUtils::encode($this);
        $randomId = RandomGenerator::getRandomHtmlId();
        ?>
        <div id="<?= $randomId ?>" class="framelix-alert">
            <div class="framelix-loading"></div> <?= Lang::get('__framelix_table_rendering__') ?></div>
        <script>
          (async function () {
            const table = FramelixTable.createFromPhpData(<?=$jsonData?>)
            table.render()
            $("#<?=$randomId?>").replaceWith(table.container)
          })()
        </script>
        <?php
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = RandomGenerator::getRandomHtmlId();
    }

    /**
     * Create a new thead row with given cell values
     * @param array $values
     * @return int The row key
     */
    public function createHeader(array $values): int
    {
        if (!isset($this->rows['thead'])) {
            $this->rows['thead'] = [];
        }
        $rowKey = count($this->rows['thead']);
        $this->setRowValues($rowKey, $values, "thead");
        return $rowKey;
    }

    /**
     * Create a new tbody row with given cell values
     * @param array $values
     * @return int The row key
     */
    public function createRow(array $values): int
    {
        if (!isset($this->rows['tbody'])) {
            $this->rows['tbody'] = [];
        }
        $rowKey = count($this->rows['tbody']);
        $this->setRowValues($rowKey, $values);
        return $rowKey;
    }

    /**
     * Create a new footer row with given cell values
     * @param array $values
     * @return int The row key
     */
    public function createFooter(array $values): int
    {
        if (!isset($this->rows['tfoot'])) {
            $this->rows['tfoot'] = [];
        }
        $rowKey = count($this->rows['tfoot']);
        $this->setRowValues($rowKey, $values, "tfoot");
        return $rowKey;
    }

    /**
     * Set/override cell values for given row
     * @param int $rowKey
     * @param array $values
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setRowValues(int $rowKey, array $values, string $group = "tbody"): void
    {
        if (!$this->columnOrder) {
            $this->columnOrder = array_keys($values);
        }
        foreach ($values as $cellName => $value) {
            $this->setCellValue($rowKey, $cellName, $value, null, $group);
        }
    }

    /**
     * Get assigned row storable
     * @param int $rowKey
     * @param string $group The group: thead, tbody, tfoot
     * @return Storable|null
     */
    public function getRowStorable(int $rowKey, string $group = "tbody"): ?Storable
    {
        return $this->rows[$group][$rowKey]['storable'] ?? null;
    }

    /**
     * Assign a storable to a row, setting some defaults for this row
     * @param int $rowKey
     * @param Storable $storable
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setRowStorable(int $rowKey, Storable $storable, string $group = "tbody"): void
    {
        $this->rows[$group][$rowKey]['storable'] = $storable;
        if ($this->storableEditable) {
            $this->setRowUrl($rowKey, $storable->getEditUrl(), $group);
        }
        if ($this->storableDeletable && $storable->isDeletable()) {
            $deleteUrl = JsCall::getCallUrl(
                __CLASS__,
                'deleteStorable',
                ['id' => $storable->id, 'connectionId' => $storable->connectionId]
            );
            $cell = new TableCell();
            $cell->icon = "clear";
            $cell->iconTooltip = "__delete_entry__";
            $cell->iconAction = "delete-storable";
            $cell->iconAttributes = new HtmlAttributes();
            $cell->iconAttributes->set('data-url', $deleteUrl);
            if (!in_array("_deletable", $this->columnOrder)) {
                array_unshift($this->columnOrder, "_deletable");
            }
            $this->setCellValue($rowKey, "_deletable", $cell, null, $group);
        }
        $attributes = ['data-id' => $storable, 'data-connection-id' => $storable->connectionId];
        $this->getRowHtmlAttributes($rowKey)->setArray($attributes);
    }

    /**
     * Assign a url to a row, where the user can click on the row to open the url
     * @param int $rowKey
     * @param Url|null $url
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setRowUrl(int $rowKey, ?Url $url, string $group = "tbody"): void
    {
        $this->getRowHtmlAttributes($rowKey, $group)->set('data-url', $url);
    }

    /**
     * Get row html attributes
     * @param int $rowKey
     * @param string $group The group: thead, tbody, tfoot
     * @return HtmlAttributes
     */
    public function getRowHtmlAttributes(int $rowKey, string $group = "tbody"): HtmlAttributes
    {
        if (!isset($this->rows[$group][$rowKey]['htmlAttributes'])) {
            $this->rows[$group][$rowKey]['htmlAttributes'] = new HtmlAttributes();
        }
        return $this->rows[$group][$rowKey]['htmlAttributes'];
    }

    /**
     * Set override a value of a single cell
     * @param int $rowKey
     * @param mixed $columnName
     * @param mixed $value
     * @param mixed $sortValue Explicit value to be used by the tablesorter, null does try to auto detect
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setCellValue(
        int $rowKey,
        mixed $columnName,
        mixed $value,
        mixed $sortValue = null,
        string $group = "tbody"
    ): void {
        if (!isset($this->rows[$group][$rowKey])) {
            $this->rows[$group][$rowKey]['rowKeyInitial'] = $rowKey;
        }
        if ($value instanceof TableCell && $value->icon) {
            $this->addColumnFlag($columnName, self::COLUMNFLAG_ICON);
            $this->addColumnFlag($columnName, self::COLUMNFLAG_IGNORESORT);
            $this->addColumnFlag($columnName, self::COLUMNFLAG_IGNOREURL);
            $this->addColumnFlag($columnName, self::COLUMNFLAG_REMOVE_IF_EMPTY);
        }
        $this->rows[$group][$rowKey]['cellValues'][$columnName] = $value;
        $this->rows[$group][$rowKey]['sortValues'][$columnName] = $sortValue;
    }

    /**
     * Set cell html attributes
     * @param int $rowKey
     * @param mixed $cellName
     * @param HtmlAttributes $attributes
     * @param string $group The group: thead, tbody, tfoot
     */
    public function setCellHtmlAttributes(
        int $rowKey,
        mixed $cellName,
        HtmlAttributes $attributes,
        string $group = "tbody"
    ) {
        $this->rows[$group][$rowKey]['cellAttributes'][$cellName] = $attributes;
    }

    /**
     * Get cell html attributes
     * @param int $rowKey
     * @param mixed $cellName
     * @param string $group The group: thead, tbody, tfoot
     * @return HtmlAttributes
     */
    public function getCellHtmlAttributes(int $rowKey, mixed $cellName, string $group = "tbody"): HtmlAttributes
    {
        if (!isset($this->rows[$group][$rowKey]['cellAttributes'][$cellName])) {
            $this->rows[$group][$rowKey]['cellAttributes'][$cellName] = new HtmlAttributes();
        }
        return $this->rows[$group][$rowKey]['cellAttributes'][$cellName];
    }

    /**
     * Add a column flag
     * @param string $columnName
     * @param string $columnFlag COLUMNFLAG_*
     */
    public function addColumnFlag(string $columnName, string $columnFlag): void
    {
        if (!isset($this->columnFlags[$columnName])) {
            $this->columnFlags[$columnName] = [];
        }
        if (!in_array($columnFlag, $this->columnFlags[$columnName])) {
            $this->columnFlags[$columnName][] = $columnFlag;
        }
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

        if ($this->footerSumColumns) {
            foreach ($this->footerSumColumns as $columnName) {
                if (!in_array($columnName, $this->columnOrder)) {
                    throw new Exception('Cell "' . $columnName . '" for footerSumColumns does not exist');
                }
            }
        }
        if ($this->footerSumColumns) {
            $this->footerSumColumns = array_combine($this->footerSumColumns, $this->footerSumColumns);
        }
        $footerSums = [];
        foreach ($properties['rows'] as $group => $rows) {
            foreach ($rows as $rowKey => $rowValues) {
                if (!isset($rowValues['cellValues'])) {
                    continue;
                }
                $storable = $this->getRowStorable($rowKey);
                foreach ($rowValues['cellValues'] as $columnName => $value) {
                    $storableSchemaProperty = $storable ? Storable::getStorableSchemaProperty(
                        $storable,
                        $columnName
                    ) : null;
                    if ($group === 'tbody' && isset($this->footerSumColumns[$columnName])) {
                        if (!isset($footerSums[$columnName])) {
                            $footerSums[$columnName] = ['value' => 0, 'type' => 'default'];
                        }
                        if (is_string($value)) {
                            $value = NumberUtils::toFloat($value);
                        }
                        if (is_int($value) || is_float($value)) {
                            $footerSums[$columnName]['type'] = 'number';
                            $footerSums[$columnName]['decimals'] = $storableSchemaProperty->decimals ?? null;
                            $footerSums[$columnName]['value'] += $value;
                        } elseif ($value instanceof Time) {
                            $footerSums[$columnName]['type'] = "time";
                            $footerSums[$columnName]['value'] += $value->seconds;
                        }
                    }
                    if (is_float($value) && $storableSchemaProperty?->decimals > 0) {
                        $value = NumberUtils::format($value, $storableSchemaProperty?->decimals);
                    }
                    $sortValue = $rowValues['sortValues'][$columnName] ?? null;
                    if ($value instanceof ObjectTranformable) {
                        $sortValue = $value->getSortableValue();
                    } elseif (is_int($value) || is_float($value) || is_bool($value)) {
                        $sortValue = is_bool($sortValue) ? (int)$sortValue : $sortValue;
                    }
                    if (!$value instanceof TableCell) {
                        $value = trim(StringUtils::stringify($value, "<br/>", ["getHtmlString"]));
                        if ($sortValue === null) {
                            $sortValue = StringUtils::slugify(mb_strtolower($value));
                        }
                    }
                    $rowValues['cellValues'][$columnName] = $value;
                    $rowValues['sortValues'][$columnName] = $sortValue;
                    $properties['rows'][$group][$rowKey] = $rowValues;
                }
            }
        }
        if ($footerSums) {
            if (!isset($properties['rows']['tfoot'])) {
                $properties['rows']['tfoot'] = [];
            }
            $row = [];
            foreach ($footerSums as $columnName => $rowValues) {
                if ($rowValues['type'] === 'time') {
                    $row[$columnName] = Time::secondsToTimeString($rowValues['value']);
                } else {
                    $row[$columnName] = NumberUtils::format($rowValues['value'], $rowValues['decimals'] ?? 2);
                }
            }
            $properties['rows']['tfoot'][]['cellValues'] = $row;
        }

        return [
            'properties' => $properties
        ];
    }

}