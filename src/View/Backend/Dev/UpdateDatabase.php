<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View\Backend\View;

use function implode;
use function sleep;

/**
 * Update database
 */
class UpdateDatabase extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "dev";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getPost('safeQueriesExecute') || Request::getPost('unsafeQueriesExecute')) {
            $shell = Shell::prepare("php {*}", [
                __DIR__ . "/../../../../console.php",
                "updateDatabaseSafe"
            ])->execute();
            Toast::info(implode("<br/>", $shell->output));
            if (Request::getPost('unsafeQueriesExecute')) {
                // wait 3 seconds to prevent opcache in default configs
                sleep(3);
                $shell = Shell::prepare("php {*}", [
                    __DIR__ . "/../../../../console.php",
                    "updateDatabaseUnsafe"
                ])->execute();
                Toast::info(implode("<br/>", $shell->output));
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
        $form = $this->getForm();
        $form->addSubmitButton("update", "__framelix_view_backend_dev_updatedatabase_updatenow__", "upgrade");
        $form->show();
    }

    /**
     * Get form update database
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "update-database";

        $builder = new MysqlStorableSchemeBuilder(Mysql::get());
        $unsafeQueries = $builder->getUnsafeQueries();
        $safeQueries = $builder->getSafeQueries();

        if (!$unsafeQueries && !$safeQueries) {
            $field = new Html();
            $field->name = "fine";
            $field->defaultValue = '<div class="framelix-alert framelix-alert-success">Everything is fine</div>';
            $form->addField($field);
        } else {
            if ($safeQueries) {
                $field = new Html();
                $field->name = "safeQueriesHtml";
                $field->label = '__framelix_view_backend_dev_updatedatabase_safequeries__';
                $field->defaultValue = '';
                foreach ($safeQueries as $row) {
                    $field->defaultValue .= '<div class="framelix-code-block">' . HtmlUtils::escape(
                            $row['query']
                        ) . ';</div>';
                }
                $form->addField($field);

                $field = new Toggle();
                $field->name = "safeQueriesExecute";
                $field->label = '__framelix_view_backend_dev_updatedatabase_safequeries_execute__';
                $field->defaultValue = true;
                $form->addField($field);
            }

            if ($unsafeQueries) {
                $field = new Html();
                $field->name = "unsafeQueriesHtml";
                $field->label = '__framelix_view_backend_dev_updatedatabase_unsafequeries__';
                $field->defaultValue = '';
                foreach ($unsafeQueries as $row) {
                    $field->defaultValue .= '<div class="framelix-code-block">' . HtmlUtils::escape(
                            $row['query']
                        ) . ';</div>';
                }
                $form->addField($field);

                $field = new Toggle();
                $field->name = "unsafeQueriesExecute";
                $field->label = '__framelix_view_backend_dev_updatedatabase_unsafequeries_execute__';
                $form->addField($field);
            }
        }
        if (!$unsafeQueries) {
            $field = new Html();
            $field->name = "fine";
            $field->defaultValue = '<div class="framelix-alert framelix-alert-success">' . Lang::get(
                    '__framelix_view_backend_dev_updatedatabase_noupdates__'
                ) . '</div>';
            $form->addField($field);
        }

        return $form;
    }
}