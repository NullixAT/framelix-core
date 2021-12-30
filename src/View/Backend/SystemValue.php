<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use ReflectionClass;

use function call_user_func_array;

/**
 * System value edit view
 */
abstract class SystemValue extends View
{

    /**
     * The storable
     * @var \Framelix\Framelix\Storable\SystemValue
     */
    protected $storableIntern;

    /**
     * The meta
     * @var \Framelix\Framelix\StorableMeta\SystemValue
     */
    protected $metaIntern;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $reflection = new ReflectionClass($this);
        $storablePropertyType = $reflection->getProperty('storable')?->getType();
        $metaPropertyType = $reflection->getProperty('meta')?->getType();

        if (!$storablePropertyType || !$storablePropertyType->getName()) {
            throw new Exception(
                "You must define a protected property 'storable' with a system value type",
                ErrorCode::SYSTEMVALUE_PROPERTY_MISSING
            );
        }

        if (!$metaPropertyType || !$metaPropertyType->getName()) {
            throw new Exception(
                "You must define a protected property 'meta' with a storable meta system value type",
                ErrorCode::SYSTEMVALUE_PROPERTY_MISSING
            );
        }

        $this->storableIntern = call_user_func_array(
            [$storablePropertyType->getName(), "getByIdOrNew"],
            [Request::getGet('id')]
        );
        if (!$this->storableIntern->id) {
            $this->storableIntern->flagActive = true;
        }
        $metaClass = $metaPropertyType->getName();
        $this->metaIntern = new $metaClass($this->storableIntern);

        if (Form::isFormSubmitted($this->metaIntern->getEditFormId())) {
            $form = $this->metaIntern->getEditForm();
            $form->validate();
            if (!$this->storableIntern->id) {
                $nextSort = $this->storableIntern::getByConditionOne(sort: ["-sort"])->sort ?? 0;
                $this->storableIntern->sort = $nextSort + 1;
            }
            $form->setStorableValues($this->storableIntern);
            $form->storeWithFiles($this->storableIntern);
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->removeParameterByValue($this->storableIntern)->redirect();
        }

        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        switch ($this->tabId) {
            case 'active':
            case 'inactive':
                $objects = $this->storableIntern::getByCondition(
                    'flagActive = {0}',
                    [$this->tabId === 'active'],
                    ['+sort']
                );
                $table = $this->metaIntern->getTableWithStorableSorting($objects, $this->tabId);
                $table->show();
                break;
            default:
                $form = $this->metaIntern->getEditForm();
                $form->show();
                $counts = Mysql::get()->fetchColumn(
                    "
                    SELECT COUNT(*), flagActive
                    FROM `" . $this->storableIntern::class . "`
                    GROUP BY flagActive
                "
                );
                if ($counts) {
                    ?>
                    <div class="framelix-spacer-x2"></div>
                    <?php
                    $tabs = new Tabs();
                    $tabs->addTab(
                        'active',
                        Lang::get('__framelix_systemvalues_active__') . " (" . ($counts[1] ?? 0) . ")",
                        new static()
                    );
                    if ($counts[0] ?? 0) {
                        $tabs->addTab(
                            'inactive',
                            Lang::get('__framelix_systemvalues_inactive__') . " (" . ($counts[0] ?? 0) . ")",
                            new static()
                        );
                    }
                    $tabs->show();
                }
        }
    }
}