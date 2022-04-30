<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View\Backend\View;

use function file_exists;
use function preg_replace;
use function str_replace;
use function strtolower;

use const FRAMELIX_MODULE;

/**
 * Configuration for a single module
 */
class ModuleConfig extends View
{
    /**
     * The forms
     * @var array
     */
    public static array $forms = [];

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin";

    /**
     * The module
     * @var string
     */
    private string $module;

    /**
     * Add a form to the current edit page
     * @param Form $form
     * @param string $label
     * @param callable|null $customStoreFunction A custom function to execute on store, instead of storing the values
     *  This is used for example in e-mail testing, which just sends an email instead of saving settings
     */
    public static function addForm(Form $form, string $label, ?callable $customStoreFunction = null): void
    {
        self::$forms[] = [
            'label' => $label,
            'form' => $form,
            'customStoreFunction' => $customStoreFunction
        ];
    }

    /**
     * Save config values from given form submitted values
     * @param Form $form
     */
    public static function saveConfig(Form $form): void
    {
        $configData = Config::getConfigFromFile(FRAMELIX_MODULE, "config-editable.php");
        if (!$configData) {
            $configData = [];
        }
        foreach ($form->fields as $field) {
            if ($field instanceof Html) {
                continue;
            }
            ArrayUtils::setValue($configData, $field->name, $field->getConvertedSubmittedValue());
        }
        Config::writeConfigToFile(FRAMELIX_MODULE, "config-editable.php", $configData);
        Config::loadModule(FRAMELIX_MODULE);
        Toast::success('__framelix_saved__');
    }

    /**
     * Load forms
     */
    public static function loadForms(string $module): void
    {
        $configFileForms = FileUtils::getModuleRootPath($module) . "/config/config-editable-form.php";
        if (file_exists($configFileForms)) {
            require $configFileForms;
        }
        foreach (self::$forms as $key => $row) {
            /** @var Form $form */
            $form = $row['form'];
            $form->id = "module-" . $module . "-" . $key;
            foreach ($form->fields as $field) {
                $langKey = strtolower($field->name);
                $langKey = str_replace("][", "_", $langKey);
                $langKey = preg_replace("~\[(.*?)\]~i", "_$1", $langKey);
                $langKey = "__framelix_config_{$langKey}_";
                $field->label = $field->label ?? $langKey . "label__";
                $field->labelDescription = $field->labelDescription ?? $langKey . "label_desc__";
                if (!Lang::keyExist($field->labelDescription)) {
                    $field->labelDescription = null;
                }
                $field->defaultValue = Config::keyExists($field->name) ? Config::get(
                    $field->name
                ) : $field->defaultValue;
            }
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (!isset(Config::$loadedModules[Request::getGet('module')])) {
            $this->showInvalidUrlError();
        }
        $this->module = Request::getGet('module');
        self::loadForms($this->module);
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $tabs = new Tabs();
        foreach (self::$forms as $key => $row) {
            $tabs->addTab(
                $key,
                $row['label'],
                new ModuleConfigTab(),
                ['module' => $this->module, 'formId' => $key]
            );
        }
        $tabs->show();
    }
}