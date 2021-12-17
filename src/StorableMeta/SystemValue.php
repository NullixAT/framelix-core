<?php

namespace Framelix\Framelix\StorableMeta;

use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\StorableMeta;

/**
 * SystemValue Storable Meta
 */
abstract class SystemValue extends StorableMeta
{
    /**
     * The storable
     * @var \Framelix\Framelix\Storable\SystemValue
     */
    public Storable $storable;

    /**
     * Get html table filled with rows for the given storable objects
     * @param Storable[] $objects
     * @param string|null $idAffix To separate multiple html tables if you use it many times
     * @return Table
     */
    public function getTable(array $objects, ?string $idAffix = null): Table
    {
        $table = parent::getTable($objects, $idAffix);
        $table->dragSort = true;
        return $table;
    }

    /**
     * Set default properties at the end
     */
    protected function addDefaultPropertiesAtEnd(): void
    {
        $property = $this->createProperty("flagActive");
        $property->setLabel('__framelix_systemvalues_active_form_label__');
        $property->addDefaultField();

        parent::addDefaultPropertiesAtEnd();
    }
}