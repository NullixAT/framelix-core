<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

use function call_user_func_array;

/**
 * Configuration for a single module of a single tab form
 */
class ModuleConfigTab extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,configuration";

    /**
     * The module
     * @var string
     */
    private string $module;

    /**
     * The current context form
     * @var Form|null
     */
    private ?Form $form = null;

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (!isset(Config::$loadedModules[Request::getGet('module')])) {
            $this->showInvalidUrlError();
        }
        $this->module = Request::getGet('module');
        ModuleConfig::loadForms($this->module);
        $this->form = ModuleConfig::$forms[Request::getGet('formId')]['form'] ?? null;
        if ($this->form && Form::isFormSubmitted($this->form->id)) {
            $customStoreFunction = ModuleConfig::$forms[Request::getGet('formId')]['customStoreFunction'];
            $this->form->validate();
            if ($customStoreFunction) {
                call_user_func_array($customStoreFunction, [$this->form]);
            } else {
                ModuleConfig::saveConfig($this->form);
            }
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if (!$this->form) {
            ?>
            <div class="framelix-alert framelix-alert-warning">
                <?= Lang::get('__framelix_config_missing_forms_file__') ?>
            </div>
            <?php
            return;
        }
        $this->form->addSubmitButton('save', '__framelix_save__', 'save');
        $this->form->show();
    }
}