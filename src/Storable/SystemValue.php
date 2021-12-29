<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Url;
use Framelix\Framelix\View;

use function get_class;
use function is_array;
use function str_replace;

/**
 * System value
 * @property int $sort
 * @property bool $flagActive
 */
abstract class SystemValue extends StorableExtended
{

    /**
     * Get entries of the called storable
     * @param mixed|null $additionalEntries Add this entries to the list, used for existing values in a storable
     * @param bool $activeOnly If true, then only list active entries
     * @return static[]
     */
    public static function getEntries(mixed $additionalEntries = null, bool $activeOnly = true): array
    {
        $condition = '1';
        if ($activeOnly) {
            $condition = 'flagActive = 1';
        }
        $entries = static::getByCondition($condition, sort: ['+sort']);
        if ($additionalEntries) {
            if (!is_array($additionalEntries)) {
                $additionalEntries = [$additionalEntries];
            }
            foreach ($additionalEntries as $additionalEntry) {
                if ($additionalEntry instanceof static && !isset($entries[$additionalEntry->id])) {
                    $entries[$additionalEntry->id] = $additionalEntry;
                }
            }
        }
        return $entries;
    }

    /**
     * Get edit url
     * @return Url|null
     */
    public function getEditUrl(): ?Url
    {
        $storableClassName = get_class($this);
        $viewClassName = str_replace("\\Storable\\SystemValue\\", "\\View\\Backend\\SystemValue\\", $storableClassName);
        return View::getUrl($viewClassName)?->setParameter('id', $this);
    }
}