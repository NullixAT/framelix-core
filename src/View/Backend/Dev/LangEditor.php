<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;

use function array_keys;
use function array_merge;
use function array_unique;
use function ceil;
use function file_exists;
use function htmlentities;
use function nl2br;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

use const SORT_ASC;

/**
 * Lang editor
 */
class LangEditor extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * Only in dev mode
     * @var bool
     */
    protected bool $devModeOnly = true;

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getBody('save')) {
            $lang = Request::getBody('lang');
            $data = Request::getBody('values');
            if ($data) {
                foreach ($data as $module => $values) {
                    foreach ($values as $key => $row) {
                        // if original have no line breaks, then we can safely convert line breaks to <br/>
                        if (!str_contains(Lang::get($key, null, 'en'), "\n")) {
                            $values[$key][0] = str_replace("\n", "<br/>", $row[0]);
                        }
                        // remove empty entries
                        if ($lang !== 'en' && $values[$key][0] === '') {
                            unset($values[$key]);
                        }
                    }
                    $path = FileUtils::getModuleRootPath($module) . "/lang";
                    $path .= "/$lang.json";
                    JsonUtils::writeToFile($path, $values, true);
                }
            }
            Toast::success('__framelix_saved__');
            Response::showFormAsyncSubmitResponse();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if ($this->tabId && str_starts_with($this->tabId, "lang-")) {
            $form = $this->getForm(substr($this->tabId, 5));
            $form->addSubmitButton('save', '__framelix_save__', 'save');
            $form->show();
        } else {
            $tabs = new Tabs();
            foreach (Lang::getAllModuleLanguages() as $language) {
                $tabs->addTab('lang-' . $language, $language, new self());
            }
            $tabs->show();
            ?>
            <style>
              .framelix-form-buttons {
                position: sticky;
                bottom: 0;
                background: var(--color-page-bg);
                padding: 20px 10px 10px;
                z-index: 1;
              }
            </style>
            <?php
        }
    }

    /**
     * Get form
     * @param string $language
     * @return Form
     */
    public function getForm(string $language): Form
    {
        $form = new Form();
        $form->id = "update";
        $form->submitAsyncRaw = true;

        $field = new Hidden();
        $field->name = 'lang';
        $field->defaultValue = $language;
        $form->addField($field);

        if ($language !== 'en') {
            $field = new Select();
            $field->name = 'visibility';
            $field->label = "Visibility";
            $field->addOption('all', 'All');
            $field->addOption('untranslated', 'Only untranslated');
            $field->defaultValue = "untranslated";
            $form->addField($field);

            $fieldTranslated = new Html();
            $fieldTranslated->name = "status";
            $form->addField($fieldTranslated);
        }

        $arr = [];
        foreach (Config::$loadedModules as $module) {
            $file = FileUtils::getModuleRootPath($module) . "/lang/$language.json";
            $values = null;
            if (file_exists($file)) {
                $values = JsonUtils::readFromFile($file);
                foreach ($values as $key => $row) {
                    $arr[$key] = [
                        'module' => $module,
                        'value' => $row[0] ?? '',
                        'hash' => $row[1] ?? '',
                        'desc' => Lang::get($key, null, 'en')
                    ];
                }
            }
            // fetch all possible keys
            $keys = [];
            $files = FileUtils::getFiles(FileUtils::getModuleRootPath($module) . "/lang", "~\.json$~", true);
            foreach ($files as $file) {
                $values = JsonUtils::readFromFile($file);
                $keys = array_merge($keys, array_keys($values));
            }
            $keys = array_unique($keys);
            sort($keys);
            foreach ($keys as $key) {
                if (!isset($arr[$key])) {
                    $arr[$key] = [
                        'module' => $module,
                        'value' => '',
                        'hash' => '',
                        'desc' => Lang::get($key, null, 'en')
                    ];
                }
            }
        }
        $totalKeys = 0;
        $translated = 0;
        ksort($arr, SORT_ASC);
        foreach ($arr as $key => $row) {
            $module = $row['module'];
            $totalKeys++;
            $hash = substr(md5($row['desc']), 0, 5);
            $field = new Textarea();
            $field->name = 'values[' . $module . '][' . $key . '][0]';
            $hashEqual = $hash === $row['hash'] && $row['value'] !== '';
            if ($hashEqual) {
                $translated++;
            }
            $field->label = '';
            if ($language !== 'en') {
                $field->label = '<span class="material-icons" style="position:relative; top:2px; color:' . ($hashEqual ? 'var(--color-success-text)' : 'var(--color-error-text)') . '">' . ($hashEqual ? 'check' : 'error') . '</span> ';
                $value = $row['desc'];
                if (!str_contains($value, "\n")) {
                    $value = str_replace(["<br/>", "<br />"], "\n", $value);
                }
                $field->labelDescription = nl2br(htmlentities($value));
                $condition = $field->getVisibilityCondition();
                if ($hashEqual) {
                    $condition->equal('visibility', 'all');
                }
            }
            $field->label .= trim($key, "_");
            $field->spellcheck = true;
            $value = $row['value'];
            if (!str_contains($value, "\n")) {
                $value = str_replace(["<br/>", "<br />"], "\n", $value);
            }
            $field->defaultValue = $value;
            $form->addField($field);
            if ($language !== 'en') {
                $field = new Hidden();
                $field->name = 'values[' . $module . '][' . $key . '][1]';
                $field->defaultValue = $hash;
                $form->addField($field);
            }
        }
        if (isset($fieldTranslated)) {
            $percent = ceil((100 / $totalKeys * $translated));
            $fieldTranslated->labelDescription = "$percent%  - $translated of $totalKeys translated";
            $fieldTranslated->defaultValue = '<progress value="' . ($percent / 100) . '" style="width: 100%"></progress>';
        }

        return $form;
    }
}