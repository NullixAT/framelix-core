<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Db\LazySearchCondition;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\View\Api;
use PHPMailer\PHPMailer\Exception;

use function get_class;
use function is_array;
use function is_string;
use function strlen;
use function trim;

/**
 * A search field
 */
class Search extends Select
{
    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'quicksearch':
                $meta = StorableMeta::createFromUrl(Url::create());
                $query = trim((string)($jsCall->parameters['query'] ?? null));
                $jsCall->result = '';
                if (strlen($query)) {
                    $condition = $meta->getQuickSearchCondition();
                    $objects = $meta->storable::getByCondition(
                        $condition->getPreparedCondition($meta->storable->getDb(), $query),
                        sort: Request::getGet('sort'),
                        limit: Request::getGet('limit')
                    );
                    $list = [];
                    foreach ($objects as $object) {
                        $list[$object->id] = $object->getHtmlString();
                    }
                    $jsCall->result = ArrayUtils::getArrayForJavascript($list);
                }
                break;
            case 'search':
                $storableClass = Request::getGet('storableClass');
                ClassUtils::validateClassName($storableClass);
                $properties = Request::getGet('properties');
                $query = trim((string)($jsCall->parameters['query'] ?? null));
                $jsCall->result = '';
                if (strlen($query)) {
                    /** @var Storable $storable */
                    $storable = new $storableClass();
                    $lazySearchCondition = new LazySearchCondition();
                    if (is_array($properties)) {
                        foreach ($properties as $property) {
                            $storableMetaProperty = Storable::getStorableSchemaProperty($storableClass, $property);
                            if (!$storableMetaProperty) {
                                throw new Exception(
                                    'Property "' . $property . '" does not exist in "' . $storableClass . '"'
                                );
                            }
                            $lazySearchCondition->addColumn(
                                "`t0`.`$storableMetaProperty->name`",
                                $storableMetaProperty->name,
                                null,
                                $storableMetaProperty->internalType
                            );
                        }
                    }
                    $condition = $lazySearchCondition->getPreparedCondition($storable->getDb(), $query);
                    $objects = $storable::getByCondition(
                        $condition,
                        sort: Request::getGet('sort'),
                        limit: Request::getGet('limit')
                    );
                    $list = [];
                    foreach ($objects as $object) {
                        $list[$object->id] = $object->getHtmlString();
                    }
                    $jsCall->result = $list;
                }
                break;
        }
    }

    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 300;

    /**
     * Is multiple
     * @var bool
     */
    public bool $multiple = false;

    /**
     * Search options
     * @var array|null
     */
    private ?array $searchMethod = null;

    /**
     * Continuous search when user input
     * If false, user must hit enter to start search
     * @var bool
     */
    public bool $continuousSearch = true;

    /**
     * Set the search to search with the same method as quick search from storable meta
     * @param string|Storable $storable
     * @param string|Storable $storableMeta
     * @param array|null $sort Sort result list by given properties, see Storable::getByCondition sort parameter
     * @param int|null $limit Limit the result to given number of entries
     */
    public function setSearchWithStorableMetaQuickSearch(
        string|Storable $storable,
        string|Storable $storableMeta,
        ?array $sort = null,
        ?int $limit = 300
    ): void {
        if (is_string($storable)) {
            /** @var Storable $storable */
            $storable = new $storable();
        }
        if (is_string($storableMeta)) {
            /** @var StorableMeta $storableMeta */
            $storableMeta = new $storableMeta($storable);
        }
        $parameters = $storableMeta->jsonSerialize();
        $parameters['sort'] = $sort;
        $parameters['limit'] = $limit;
        $this->setSearchMethod(__CLASS__, "quicksearch", $parameters, 'storablemeta');
    }

    /**
     * Set the search to search in a storable in given properties with lazy search
     * @param string|Storable $storable
     * @param array $properties
     * @param array|null $sort Sort result list by given properties, see Storable::getByCondition sort parameter
     * @param int|null $limit Limit the result to given number of entries
     */
    public function setSearchWithStorable(
        string|Storable $storable,
        array $properties,
        ?array $sort = null,
        ?int $limit = 300
    ): void {
        $this->setSearchMethod(
            __CLASS__,
            "search",
            [
                'storableClass' => $storable,
                'properties' => $properties,
                'sort' => $sort,
                'limit' => $limit
            ],
            'storable'
        );
    }

    /**
     * Set search method - Call will be done with FramelixApi.callPhpMethod()
     * @param string|null $callableMethod Could be class name only, then onJsCall is the method name
     * @param string $action The action
     * @param array|null $parameters Parameters to pass by
     * @param string $internalType Internal type to distinguish between search functionality
     */
    public function setSearchMethod(
        ?string $callableMethod,
        string $action,
        ?array $parameters = null,
        string $internalType = 'default'
    ): void {
        $this->searchMethod = [
            'type' => $internalType,
            'callableMethod' => $callableMethod,
            "action" => $action,
            "parameters" => $parameters
        ];
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        if (!$this->searchMethod) {
            throw new \Exception('Missing search method for ' . get_class($this));
        }
        $properties = parent::jsonSerialize();
        $properties['properties']['signedUrlSearch'] = Api::getSignedCallPhpMethodUrlString(
            $this->searchMethod['callableMethod'],
            $this->searchMethod['action'],
            $this->searchMethod['parameters']
        );
        if ($this->defaultValue) {
            $type = $this->searchMethod['type'];
            if ($type === 'storable' || $type === 'storablemeta') {
                $properties['properties']['initialSelectedOptions'] = [];
                $defaultValues = !is_array($this->defaultValue) ? [$this->defaultValue] : $this->defaultValue;
                $list = [];
                foreach ($defaultValues as $defaultValue) {
                    if ($defaultValue instanceof Storable) {
                        $list[$defaultValue->id] = $defaultValue->getHtmlString();
                    }
                }
                $properties['properties']['initialSelectedOptions'] = $list;
            } elseif ($type === 'default') {
                $jsCall = new JsCall(
                    $this->searchMethod['action'],
                    ArrayUtils::merge($this->searchMethod['parameters'], ['defaultValue' => $this->defaultValue])
                );
                $properties['properties']['initialSelectedOptions'] = $jsCall->call(
                    $this->searchMethod['callableMethod']
                );
            }
        }
        if (isset($properties['properties']['initialSelectedOptions'])) {
            $properties['properties']['initialSelectedOptions'] = ArrayUtils::getArrayForJavascript(
                $properties['properties']['initialSelectedOptions']
            );
        }
        return $properties;
    }
}