<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Console;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\Utils\Zip;
use ZipArchive;

use function file_exists;
use function file_put_contents;
use function htmlentities;
use function set_time_limit;
use function strlen;
use function substr;
use function unlink;

use const FILE_APPEND;
use const FRAMELIX_APP_ROOT;

/**
 * AppUpdate
 */
class AppUpdate extends View
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
        if (Request::getPost('updateType') === 'upload') {
            Console::$htmlOutput = true;
            $files = UploadedFile::createFromSubmitData('file');
            if ($files) {
                $file = reset($files);
                Buffer::start();
                Console::installZipPackage($file->path);
                $output = Buffer::get();
                Session::set('appupdate-lastresult', $output);
            }
            Url::getBrowserUrl()->redirect();
        }

        if (Request::getGet('backupdownload')) {
            set_time_limit(0);
            $rand = RandomGenerator::getRandomString(60, 70);
            $zipFile = FileUtils::getModuleRootPath("Framelix") . "/tmp/backup-$rand.zip";
            $backupSqlFile = FileUtils::getModuleRootPath("Framelix") . "/tmp/backup-$rand.sql";
            if (file_exists($backupSqlFile)) {
                unlink($backupSqlFile);
            }
            $db = Mysql::get();
            $tables = $db->fetchColumn('SHOW TABLES FROM `' . $db->connectionConfig['database'] . '`');
            foreach ($tables as $table) {
                file_put_contents(
                    $backupSqlFile,
                    $db->fetchAssocOne('SHOW CREATE TABLE `' . $table . '`')['Create Table'] . ";\n",
                    FILE_APPEND
                );
                $rows = $db->fetchAssoc("SELECT * FROM `$table`");
                foreach ($rows as $row) {
                    $insert = 'INSERT INTO `' . $table . '` (';
                    foreach ($row as $key => $value) {
                        $insert .= "`$key`, ";
                    }
                    $insert = substr($insert, 0, -2) . ") VALUES (";
                    foreach ($row as $key => $value) {
                        $insert .= $db->escapeValue($value) . ", ";
                    }
                    $insert = substr($insert, 0, -2) . ");\n";
                    file_put_contents(
                        $backupSqlFile,
                        $insert,
                        FILE_APPEND
                    );
                }
            }
            $files = FileUtils::getFiles(FRAMELIX_APP_ROOT, null, true, true);
            foreach ($files as $file) {
                $file = FileUtils::normalizePath($file);
                $relativeName = substr($file, strlen(FRAMELIX_APP_ROOT) + 1);
                $arr["appfiles/" . $relativeName] = $file;
            }
            $arr["appdatabase/backup.sql"] = $backupSqlFile;
            Zip::createZip($zipFile, $arr, ZipArchive::CM_STORE);
            unlink($backupSqlFile);
            Response::download($zipFile, "backup.zip", afterDownload: function () use ($zipFile) {
                @unlink($zipFile);
            });
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if ($lastResult = Session::get('appupdate-lastresult')) {
            echo '<h2>' . Lang::get('__framelix_view_backend_appupdate_lastresult__') . '</h2>';
            echo '<pre>' . $lastResult . '</pre>';
            echo '<div class="framelix-spacer-x4"></div>';
        }
        $backupUrl = $this->getSelfUrl()->setParameter('backupdownload', 1);
        echo '<h2>' . Lang::get('__framelix_view_backend_appupdate_backup__') . '</h2>';
        echo '<p>' . Lang::get('__framelix_view_backend_appupdate_backup_desc__') . '</p>';
        echo '<a href="' . $backupUrl . '" class="framelix-button framelix-button-primary">' . Lang::get(
                '__framelix_view_backend_appupdate_backup_download__'
            ) . '</a>';

        echo '<div class="framelix-spacer-x4"></div>';
        echo '<h2>' . Lang::get('__framelix_view_backend_appupdate__') . '</h2>';
        $form = $this->getForm();
        $form->addSubmitButton('update1', '__framelix_view_backend_appupdate_do_update1__', 'system_update');
        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "appupdate";

        $field = new Select();
        $field->name = "updateType";
        $field->label = "__framelix_appupdate_updatetype_label__";
        $field->required = true;
        // todo enable as soon as repository get public
        // $field->addOption('stable', '__framelix_appupdate_updatetype_stable__');
        // $field->addOption('prerelease', '__framelix_appupdate_updatetype_prerelease__');
        $field->addOption('upload', '__framelix_appupdate_updatetype_upload__');
        $field->defaultValue = 'stable';
        $form->addField($field);

        $field = new File();
        $field->name = "file";
        $field->label = "__framelix_appupdate_file__";
        $field->required = true;
        $field->allowedFileTypes = ".zip";
        $field->getVisibilityCondition()->equal('updateType', 'upload');
        $form->addField($field);

        $field = new Toggle();
        $field->name = "confirm";
        $field->label = "__framelix_appupdate_confirm__";
        $field->required = true;
        $field->getVisibilityCondition()->equal('updateType', 'upload');
        $form->addField($field);

        return $form;
    }


    /**
     * Get form send mail
     * @return Form
     */
    public function getFormSendMail(): Form
    {
        $form = new Form();
        $form->id = "forgot";
        $form->submitWithEnter = true;

        $field = new Email();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $field->required = true;
        $form->addField($field);

        if (Config::get('loginCaptcha')) {
            $field = new Captcha();
            $field->name = "captcha";
            $field->required = true;
            $field->trackingAction = "framelix_backend_forgot_password";
            $form->addField($field);
        }

        return $form;
    }
}