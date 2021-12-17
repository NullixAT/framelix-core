<?php

namespace Framelix\Framelix\View\Backend\Tests;

use Framelix\Framelix\Form\Field\Bic;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Color;
use Framelix\Framelix\Form\Field\Date;
use Framelix\Framelix\Form\Field\DateTime;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Grid;
use Framelix\Framelix\Form\Field\Hidden;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Iban;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Search;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Time;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\View\Backend\View;

use function get_class;
use function str_repeat;
use function var_dump;
use function wordwrap;

class FormsDemo extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Only in dev mode
     * @var bool
     */
    protected bool $devModeOnly = true;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'search':
                $list = [];
                for ($i = 0; $i <= 300; $i++) {
                    $list[] = "Your query: " . str_repeat($jsCall->parameters['query'], $i);
                }
                $jsCall->result = $list;
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Form::isFormSubmitted('demo')) {
            $form = $this->getForm();
            if ($form->validate()) {
                var_dump($form->getConvertedSubmittedValues());
                return;
            }
        }
        $this->showContentWithLayout();
    }

    /**
     * Show the page content without layout
     */
    public function showContent(): void
    {
        $form = $this->getForm();

        $form->addSubmitButton("save", "Speichern", "save");
        $form->addLoadUrlButton(\Framelix\Framelix\View::getUrl(__CLASS__), "Cancel");

        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "demo";

        $field = new Bic();
        $field->name = "bic";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Captcha();
        $field->name = "captchav2";
        $field->label = get_class($field) . " V2";
        $field->type = $field::TYPE_RECAPTCHA_V2;
        $form->addField($field);

        $field = new Captcha();
        $field->name = "captchav3";
        $field->label = get_class($field) . " V3";
        $field->type = $field::TYPE_RECAPTCHA_V3;
        $form->addField($field);

        $field = new Color();
        $field->name = "color";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Date();
        $field->name = "date";
        $field->label = get_class($field);
        $field->minDate = \Framelix\Framelix\Date::create("now");
        $field->defaultValue = \Framelix\Framelix\Date::create("now - 1 year");
        $form->addField($field);

        $field = new DateTime();
        $field->name = "datetime";
        $field->label = get_class($field);
        $field->minDateTime = \Framelix\Framelix\DateTime::create("now");
        $field->defaultValue = \Framelix\Framelix\DateTime::create("now - 1 year");
        $form->addField($field);

        $field = new DateTime();
        $field->name = "datetime2";
        $field->label = get_class($field) . "seconds";
        $field->minDateTime = \Framelix\Framelix\DateTime::create("now");
        $field->defaultValue = \Framelix\Framelix\DateTime::create("now - 1 year");
        $field->allowSeconds = true;
        $form->addField($field);

        $field = new File();
        $field->name = "file";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new File();
        $field->name = "file";
        $field->label = get_class($field) . " multiple";
        $field->multiple = true;
        $field->required = true;
        $form->addField($field);

        $grid = new Grid();
        $grid->name = "grid";
        $grid->label = get_class($grid);
        $form->addField($grid);

        $field = new Hidden();
        $field->name = "dbid";
        $grid->addField($field);

        $field = new Date();
        $field->name = "date";
        $field->label = get_class($field);
        $field->required = true;
        $grid->addField($field);

        $field = new Textarea();
        $field->name = "text";
        $field->label = get_class($field);
        $grid->addField($field);

        $grid = new Grid();
        $grid->name = "grid2";
        $grid->label = get_class($grid) . " full width";
        $grid->fullWidth = true;
        $form->addField($grid);

        $field = new Hidden();
        $field->name = "dbid";
        $grid->addField($field);

        $field = new Date();
        $field->name = "date";
        $field->label = get_class($field);
        $grid->addField($field);

        $field = new Text();
        $field->name = "text";
        $field->label = get_class($field);
        $grid->addField($field);

        $field = new Hidden();
        $field->name = "hidden";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Html();
        $field->name = "html";
        $field->label = get_class($field);
        $field->defaultValue = "This is some <b>Html</b>";
        $form->addField($field);

        $field = new Iban();
        $field->name = "iban";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Number();
        $field->name = "decimal";
        $field->label = get_class($field);
        $field->labelDescription = "Description: " . get_class($field);
        $form->addField($field);

        $field = new Password();
        $field->name = "password";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Select();
        $field->name = "select1";
        $field->label = get_class($field) . " single";
        for ($i = 0; $i <= 3; $i++) {
            $field->addOption("o$i", "Option $i");
        }
        $form->addField($field);

        $field = new Select();
        $field->name = "select2";
        $field->label = get_class($field) . " single many";
        for ($i = 0; $i <= 100; $i++) {
            $field->addOption("o$i", "Option $i");
        }
        $field->defaultValue = "o4";
        $form->addField($field);

        $field = new Select();
        $field->name = "select2nd";
        $field->label = get_class($field) . " single many no dropdown";
        for ($i = 0; $i <= 10; $i++) {
            $field->addOption("o$i", "Option $i");
        }
        $field->dropdown = false;
        $field->defaultValue = "o4";
        $form->addField($field);

        $field = new Select();
        $field->name = "select3";
        $field->label = get_class($field) . " multiple large searchable";
        $field->multiple = true;
        $field->searchable = true;
        for ($i = 0; $i <= 100; $i++) {
            $field->addOption("o$i", "Option " . wordwrap(str_repeat($i, $i), 20, " ", true));
        }
        $field->defaultValue = ["o3", "o6", "o9"];
        $form->addField($field);

        $field = new Select();
        $field->name = "select4nd";
        $field->label = get_class($field) . " multiple no dropdown";
        for ($i = 0; $i <= 10; $i++) {
            $field->addOption("o$i", "Option $i");
        }
        $field->multiple = true;
        $field->dropdown = false;
        $field->defaultValue = "o4";
        $form->addField($field);

        $field = new Search();
        $field->name = "search_method";
        $field->label = get_class($field) . " normal method";
        $field->setSearchMethod(__CLASS__, "search");
        $form->addField($field);

        $field = new Search();
        $field->name = "search_user";
        $field->label = get_class($field) . " in storable user";
        $field->multiple = true;
        $field->setSearchWithStorable(User::class, ["email"]);
        $form->addField($field);

        $field = new Search();
        $field->name = "search_usermeta";
        $field->label = get_class($field) . " in storable meta user";
        $field->setSearchWithStorableMetaQuickSearch(User::class, \Framelix\Framelix\StorableMeta\User::class);
        $field->defaultValue = User::getByConditionOne();
        $form->addField($field);

        $field = new Search();
        $field->name = "search_usermeta_m";
        $field->label = get_class($field) . " multiple in storable meta user";
        $field->multiple = true;
        $field->setSearchWithStorableMetaQuickSearch(User::class, \Framelix\Framelix\StorableMeta\User::class);
        $field->defaultValue = User::getByConditionOne();
        $form->addField($field);

        $field = new Select();
        $field->name = "select4";
        $field->label = get_class($field) . " multiple medium no dropdown";
        $field->multiple = true;
        $field->dropdown = false;
        for ($i = 0; $i <= 10; $i++) {
            $field->addOption("o$i", "Option " . wordwrap(str_repeat($i, $i), 20, " ", true));
        }
        $field->defaultValue = ["o3", "o6", "o9"];
        $form->addField($field);

        $field = new Text();
        $field->name = "text";
        $field->label = get_class($field);
        $field->labelDescription = "Description: " . get_class($field);
        $form->addField($field);

        $field = new Text();
        $field->name = "text_autocomplete";
        $field->label = get_class($field) . " autocomplete";
        $field->autocompleteSuggestions = ['2foo', 'bar', '123'];
        $form->addField($field);

        $field = new Textarea();
        $field->name = "textarea";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Time();
        $field->name = "time";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Time();
        $field->name = "time2";
        $field->label = get_class($field) . " seconds";
        $field->allowSeconds = true;
        $form->addField($field);

        $field = new Toggle();
        $field->name = "toggle1";
        $field->label = get_class($field);
        $form->addField($field);

        $field = new Toggle();
        $field->name = "toggle2";
        $field->label = get_class($field) . " checkbox";
        $field->style = $field::STYLE_CHECKBOX;
        $form->addField($field);

        return $form;
    }
}