<?php

namespace Framelix\Framelix\View\Backend\Config;

use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View\Backend\View;

use function file_put_contents;
use function str_contains;

use const FRAMELIX_MODULE;

/**
 * Backup/Restore configuration
 */
class BackupRestore extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,configuration";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (Request::getGet('download')) {
            Response::download(
                FileUtils::getModuleRootPath(FRAMELIX_MODULE) . "/config/config-editable.php",
                "config.txt"
            );
        }
        if (Form::isFormSubmitted('backuprestore')) {
            $file = UploadedFile::createFromSubmitData("file");
            $configData = $file[0]->getFileData();
            if (!str_contains($configData, '!defined("FRAMELIX_MODULE")') || !str_contains($configData, '$config')) {
                Response::showFormValidationErrorResponse('__framelix_backupreestore_invalidfile__');
            }
            file_put_contents(
                FileUtils::getModuleRootPath(FRAMELIX_MODULE) . "/config/config-editable.php",
                $configData
            );
            Toast::success('__framelix_backupreestore_restored__');
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        ?>
        <div class="framelix-alert">
            <?= Lang::get('__framelix_backupreestore__') ?>
        </div>
        <div class="framelix-spacer"></div>
        <h2><?= Lang::get('__framelix_backupreestore_download__') ?></h2>
        <div class="framelix-spacer"></div>
        <a href="<?= \Framelix\Framelix\View::getUrl(__CLASS__)->setParameter('download', 1) ?>"
           class="framelix-button framelix-button-primary"><?= Lang::get('__framelix_backupreestore_download__') ?></a>
        <div class="framelix-spacer-x4"></div>
        <h2><?= Lang::get('__framelix_backupreestore_restore__') ?></h2>
        <?php
        $form = $this->getForm();
        $form->addSubmitButton('save', '__framelix_backupreestore_restore__');
        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "backuprestore";

        $field = new File();
        $field->name = "file";
        $field->allowedFileTypes = ".txt";
        $field->required = true;
        $form->addField($field);
        return $form;
    }
}