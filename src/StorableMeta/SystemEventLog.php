<?php

namespace Framelix\Framelix\StorableMeta;

use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

use function htmlentities;
use function is_array;

/**
 * SystemEventLog
 */
class SystemEventLog extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Framelix\Storable\SystemEventLog
     */
    public Storable $storable;

    /**
     * Initialize this meta
     */
    protected function init(): void
    {
        $this->addDefaultPropertiesAtStart();
        $property = $this->createProperty("info");
        $property->lazySearchConditionColumns->addColumn("message", "message");
        $property->lazySearchConditionColumns->addColumn("params", "params");
        $property->valueCallable = function () {
            $message = $this->storable->message;
            $category = $this->storable->category;
            if ($message === null) {
                $message = "__framelix_systemeventlog_" . $category . "__";
            }
            $params = $this->storable->params;
            if (is_array($params)) {
                foreach ($params as $key => $value) {
                    $params[$key] = htmlentities($value);
                }
            }
            if ($category === \Framelix\Framelix\Storable\SystemEventLog::CATEGORY_STORABLE_CREATED || $category === \Framelix\Framelix\Storable\SystemEventLog::CATEGORY_STORABLE_UPDATED) {
                $object = Storable::getById($params['id'], $params['connectionId']);
                if ($object && $object->getEditUrl()) {
                    $params['id'] = '<a href="' . $object->getEditUrl() . '" target="_blank">' . $object . '</a>';
                }
            }
            return Lang::get($message, $params);
        };
        $this->addDefaultPropertiesAtEnd();
    }
}