<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Db\Mysql;
use Framelix\Framelix\Db\MysqlStorableSchemeBuilder;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Throwable;

use function file_exists;
use function http_response_code;
use function strtolower;

use const FRAMELIX_MODULE;

/**
 * Setup interface for application setup
 */
class Setup extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Custom url
     * @var string|null
     */
    protected ?string $customUrl = "/setup";

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (file_exists(FileUtils::getModuleRootPath(FRAMELIX_MODULE . "/config/config-editable.php"))) {
            http_response_code(500);
            echo "This application is already setup";
            die();
        }
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showSidebar = false;
        if (Form::isFormSubmitted('setup')) {
            $form = $this->getForm();
            $form->validate();
            if (Request::getPost('password') !== Request::getPost('password2')) {
                Response::showFormValidationErrorResponse(['password2' => '__framelix_password_notmatch__']);
            }
            try {
                Config::set('database[default]', [
                    "host" => Request::getPost('dbHost'),
                    "username" => Request::getPost('dbUser'),
                    "password" => Request::getPost('dbPassword'),
                    "database" => Request::getPost('dbName'),
                    "port" => Request::getPost('dbPort') ? (int)Request::getPost('dbPort') : null,
                    "socket" => Request::getPost('dbSocket')
                ]);
                $url = Url::create(strtolower(Request::getPost('applicationUrl')));
                Config::set('applicationHttps', $url->urlData['scheme'] === "https");
                Config::set('applicationHost', $url->urlData['host']);
                Config::set('applicationUrlBasePath', $url->urlData['path']);
                Config::set('salts[general]', RandomGenerator::getRandomString(64, 70));
                Mysql::get()->query(
                    "CREATE TABLE `__framelix_test__` (
                    `id` BIGINT(18) UNSIGNED NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id`) USING BTREE
                )"
                );
                Mysql::get()->query("DROP TABLE `__framelix_test__`");
                $builder = new MysqlStorableSchemeBuilder(Mysql::get());
                $queries = $builder->getQueries();
                foreach ($queries as $row) {
                    $builder->db->query($row['query']);
                }
                $user = User::getByConditionOne('email = {0}', [Request::getPost('email')]);
                if (!$user) {
                    $user = new User();
                    $user->email = Request::getPost('email');
                    $user->roles = ['admin'];
                    $user->flagLocked = false;
                }
                $user->flagLocked = false;
                $user->addRole("admin");
                $user->setPassword(Request::getPost('password'));
                $user->store();

                $token = UserToken::create($user);
                UserToken::setCookieValue($token->token);
                $keys = [
                    'applicationHttps',
                    'applicationHost',
                    'applicationUrlBasePath',
                    'database',
                    'salts',
                    'languageDefault',
                    'languageFallback',
                    'languageMultiple'
                ];
                $configData = [];
                foreach ($keys as $key) {
                    $configData[$key] = Config::get($key);
                }
                $configData['errorLogDisk'] = true;
                Config::writetConfigToFile(FRAMELIX_MODULE, "config-editable.php", $configData);
                Url::getApplicationUrl()->redirect();
            } catch (Throwable $e) {
                Response::showFormValidationErrorResponse($e->getMessage() . $e->getTraceAsString());
            }
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton('setup', '__framelix_setup_finish_setup__', 'check');
        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "setup";

        $field = new Html();
        $field->name = "headerWelcome";
        $field->defaultValue = '<p>' . Lang::get('__framelix_setup_description__') . '</p><h2>' . Lang::get(
                '__framelix_setup_step_application_title__'
            ) . '</h2>';
        $form->addField($field);

        $field = new Text();
        $field->name = "applicationUrl";
        $field->label = "__framelix_setup_applicationurl_label__";
        $field->labelDescription = "__framelix_setup_applicationurl_desc__";
        $field->required = true;
        $field->type = "url";
        $field->defaultValue = Url::getApplicationUrl()->getUrlAsString();
        $form->addField($field);

        $field = new Html();
        $field->name = "headerDatabase";
        $field->defaultValue = '<h2>' . Lang::get('__framelix_setup_step_database_title__') . '</h2>';
        $form->addField($field);

        $field = new Text();
        $field->name = "dbHost";
        $field->label = "__framelix_setup_dbhost_label__";
        $field->defaultValue = "localhost";
        $form->addField($field);

        $field = new Text();
        $field->name = "dbUser";
        $field->label = "__framelix_setup_dbuser_label__";
        $field->required = true;
        $form->addField($field);

        $field = new Password();
        $field->name = "dbPassword";
        $field->label = "__framelix_setup_dbpassword_label__";
        $form->addField($field);

        $field = new Text();
        $field->name = "dbName";
        $field->label = "__framelix_setup_dbname_label__";
        $field->required = true;
        $field->defaultValue = strtolower(FRAMELIX_MODULE);
        $form->addField($field);

        $field = new Number();
        $field->name = "dbPort";
        $field->label = "__framelix_setup_dbport_label__";
        $field->commaSeparator = "";
        $field->thousandSeparator = "";
        $field->defaultValue = 3306;
        $form->addField($field);

        $field = new Text();
        $field->name = "dbSocket";
        $field->label = "__framelix_setup_dbsocket_label__";
        $form->addField($field);

        $field = new Html();
        $field->name = "headerSecurity";
        $field->defaultValue = '<h2>' . Lang::get('__framelix_setup_step_security_desc__') . '</h2>';
        $form->addField($field);

        $field = new Email();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $form->addField($field);

        $field = new Password();
        $field->name = "password";
        $field->label = "__framelix_password__";
        $field->minLength = 8;
        $form->addField($field);

        $field = new Password();
        $field->name = "password2";
        $field->label = "__framelix_password_repeat__";
        $field->minLength = 8;
        $form->addField($field);

        return $form;
    }
}