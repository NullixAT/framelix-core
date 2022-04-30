<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Config;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Lang;
use Framelix\Framelix\View\Backend\View;

use function strtolower;

/**
 * Tab view for configuration
 */
class Index extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin";

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        ?>
        <div class="framelix-alert framelix-alert-warning">
            <?= Lang::get('__framelix_configuration_warning__') ?>
        </div>
        <div class="framelix-spacer"></div>
        <?php
        $tabs = new Tabs();
        foreach (Config::$loadedModules as $module) {
            ModuleConfig::$forms = [];
            ModuleConfig::loadForms($module);
            if (!ModuleConfig::$forms) {
                continue;
            }
            $tabs->addTab(
                $module,
                $module === "Framelix" ? "__framelix_configuration_module_pagetitle__" : Lang::get(
                    '__' . strtolower($module) . "_modulename__"
                ),
                new ModuleConfig(),
                ["module" => $module]
            );
        }
        $tabs->addTab("systemcheck", null, new SystemCheck());
        $tabs->show();
    }
}