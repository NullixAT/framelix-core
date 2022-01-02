<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Console;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Tabs;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\Utils\Zip;
use ZipArchive;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function set_time_limit;
use function stream_context_create;
use function strlen;
use function substr;
use function unlink;

use function var_dump;

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
        if (Request::getGet('autoupdate')) {
            $updateAppUpdateFile = __DIR__ . "/../../../tmp/app-update.json";
            if (file_exists($updateAppUpdateFile)) {
                $updateData = JsonUtils::readFromFile($updateAppUpdateFile);
                if (isset($updateData['assets'])) {
                    foreach ($updateData['assets'] as $row) {
                        if ($row['name'] === 'release-' . $updateData['tag_name'] . ".zip") {
                            $context = stream_context_create([
                                    'http' => [
                                        'method' => "GET",
                                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36'
                                    ]
                                ]
                            );
                            $updateAppUpdateZipFile = __DIR__ . "/../../../tmp/app-update.zip";
                            file_put_contents(
                                $updateAppUpdateZipFile,
                                file_get_contents($row['browser_download_url'], false, $context)
                            );
                            Buffer::start();
                            Console::$htmlOutput = true;
                            Console::installZipPackage($updateAppUpdateZipFile);
                            unlink($updateAppUpdateZipFile);
                            unlink($updateAppUpdateFile);
                            $output = Buffer::get();
                            Session::set('appupdate-lastresult', $output);
                        }
                    }
                }
            }
            Url::create()->removeParameter('autoupdate')->redirect();
        }

        if (Request::getGet('check-for-updates')) {
            Console::checkAppUpdates();
            $updateAppUpdateFile = __DIR__ . "/../../../tmp/app-update.json";
            Toast::success(
                file_exists(
                    $updateAppUpdateFile
                ) ? '__framelix_appupdate_update_available__' : '__framelix_appupdate_no_update_available__'
            );
            Url::create()->removeParameter('check-for-updates')->redirect();
        }

        if (Request::getPost('update1')) {
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
                    foreach ($row as $value) {
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
        $updateAppUpdateFile = __DIR__ . "/../../../tmp/app-update.json";
        switch ($this->tabId) {
            case 'update-log':
                if ($lastResult = Session::get('appupdate-lastresult')) {
                    echo '<h2>' . Lang::get('__framelix_view_backend_appupdate_lastresult__') . '</h2>';
                    echo '<pre>' . $lastResult . '</pre>';
                    echo '<div class="framelix-spacer-x4"></div>';
                }
                break;
            case 'appupdate':
                if (file_exists($updateAppUpdateFile)) {
                    $updateData = JsonUtils::readFromFile($updateAppUpdateFile);
                    echo Lang::get('__framelix_appupdate_update_available__');
                    echo '<div class="framelix-spacer"></div>';
                    echo '<h2>' . Lang::get(
                            '__framelix_appupdate_changelog__'
                        ) . ' ' . $updateData['tag_name'] . '</h2>';
                    echo '<div>' . HtmlUtils::escape($updateData['body'], true) . '</div>';
                    echo '<div class="framelix-spacer-x2"></div>';

                    echo '<button class="framelix-button framelix-button-primary auto-update" data-icon-left="save">' . Lang::get(
                            '__framelix_appupdate_autoupdate__'
                        ) . '</button>';
                } else {
                    echo Lang::get('__framelix_appupdate_no_update_available__');
                }
                echo '<div class="framelix-spacer-x4"></div>';
                echo '<a href="?check-for-updates=1" class="framelix-button framelix-button-success" data-icon-left="update">' . Lang::get(
                        '__framelix_appupdate_check_update__'
                    ) . '</a>';
                ?>
                <script>
                  $('.auto-update').on('click', async function () {
                    if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
                      Framelix.showProgressBar(1)
                      window.location.href = '<?=$this->getSelfUrl()->setParameter('autoupdate', '1')?>'
                    }
                  })
                </script>
                <?php
                break;
            case 'backup':
                $backupUrl = $this->getSelfUrl()->setParameter('backupdownload', 1);
                echo '<h2>' . Lang::get('__framelix_view_backend_appupdate_backup__') . '</h2>';
                echo '<p>' . Lang::get('__framelix_view_backend_appupdate_backup_desc__') . '</p>';
                echo '<a href="' . $backupUrl . '" class="framelix-button framelix-button-primary">' . Lang::get(
                        '__framelix_view_backend_appupdate_backup_download__'
                    ) . '</a>';
                break;
            case 'upload':
                $form = $this->getForm();
                $form->addSubmitButton('update1', '__framelix_view_backend_appupdate_do_update1__', 'system_update');
                $form->show();
                break;
            default:
                $tabs = new Tabs();
                if (Session::get('appupdate-lastresult')) {
                    $tabs->addTab('update-log', '__framelix_appupdate_tabs_update_log__', new self());
                }
                $tabs->addTab('appupdate', '__framelix_appupdate_tabs_appupdate__', new self());
                $tabs->addTab('backup', '__framelix_appupdate_tabs_backup__', new self());
                $tabs->addTab('upload', '__framelix_appupdate_tabs_upload__', new self());
                $tabs->show();
        }
    }

    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "appupdate";

        $field = new File();
        $field->name = "file";
        $field->label = "__framelix_appupdate_file__";
        $field->required = true;
        $field->allowedFileTypes = ".zip";
        $form->addField($field);

        $field = new Toggle();
        $field->name = "confirm";
        $field->label = "__framelix_appupdate_confirm__";
        $field->required = true;
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