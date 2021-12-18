<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;
use Framelix\Framelix\View\Backend\View;
use SimpleXMLElement;

use function implode;
use function is_dir;
use function sleep;

use const FRAMELIX_APP_ROOT;

/**
 * Update source code
 */
class UpdateSourceCode extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "dev";

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'check-svn-status':
                $shell = Shell::prepare("update-source-svn status -u --xml {*}", [FRAMELIX_APP_ROOT])->execute();
                $output = $shell->output;
                if ($shell->status) {
                    $jsCall->result = implode(",", $output);
                    return;
                }
                $xml = new SimpleXMLElement(implode("\n", $output));
                $result = [];
                foreach ($xml->target->entry as $entry) {
                    $item = (string)$entry->{"wc-status"}->attributes()['item'];
                    if ($item == "external") {
                        continue;
                    }
                    $result[] = $item . ") " . $entry->attributes()['path'];
                }
                if (!$result) {
                    $result[] = '<div class="framelix-alert framelix-alert-success">' . Lang::get(
                            '__framelix_view_backend_dev_updatesourcecode_noupdates__'
                        ) . '</div>';
                }
                $jsCall->result = implode("<br/>", $result);
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getPost('update-source')) {
            if (Request::getPost('update-svn')) {
                $shell = Shell::prepare("update-source-svn up {*}", [FRAMELIX_APP_ROOT])->execute();
                if ($shell->status) {
                    Toast::error('Command error: ' . implode("<br/>", $shell->output));
                } else {
                    Toast::success('__framelix_view_backend_dev_updatesourcecode_updatedone__');
                }
            }
            if (Request::getPost('dbupdate')) {
                // sleep for 3 seconds because default opcache is configured to check only each 2 seconds for file updates
                sleep(3);
                $shell = Shell::prepare("php {*}", [
                    __DIR__ . "/../../../../console.php",
                    "updateDatabaseSafe"
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
        if ($this->isSvn()) {
            $form->addSubmitButton("update-svn", '__framelix_view_backend_dev_updatesourcecode_svnupdate__', "upgrade");
        }
        $form->show();
        ?>
        <script>
          (async function () {
            const isSvn = <?=JsonUtils::encode($this->isSvn())?>;
            const form = FramelixForm.getById('<?=$form->id?>')
            const status = form.fields['status']
            if (isSvn) {
              status.setValue(await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                  __CLASS__,
                  'check-svn-status'
              )?>'))
            }
          })()
        </script>
        <?
    }

    /**
     * Get form update database
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "update-source";

        $field = new Html();
        $field->name = "status";
        $field->defaultValue = '<div class="framelix-loading"></div>';
        $form->addField($field);

        $field = new Toggle();
        $field->name = "dbupdate";
        $field->label = "Run Database update after source update";
        $field->defaultValue = true;
        $form->addField($field);

        return $form;
    }

    /**
     * Check if app is under svn version control
     * @return bool
     */
    private function isSvn(): bool
    {
        return is_dir(FRAMELIX_APP_ROOT . "/.svn");
    }
}