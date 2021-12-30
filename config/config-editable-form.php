<?php
// prevent loading directly in the browser without framelix context
use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Number;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Form\Field\Textarea;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\View\Backend\Config\ModuleConfig;

if (!defined("FRAMELIX_MODULE")) {
    die();
}
// this file hold all editable fields for the configuration management in the backend
$form = new Form();

$field = new Toggle();
$field->name = "applicationHttps";
$form->addField($field);

$field = new Text();
$field->name = "applicationHost";
$field->required = true;
$form->addField($field);

$field = new Text();
$field->name = "applicationUrlBasePath";
$form->addField($field);
ModuleConfig::addForm($form, '__framelix_configuration_module_url_pagetitle__');

$form = new Form();

$supportedLanguages = Lang::getAllModuleLanguages();

$field = new Select();
$field->name = "languageDefault";
$field->required = true;
foreach ($supportedLanguages as $language) {
    $field->addOption($language, $language);
}
$form->addField($field);

$field = new Select();
$field->name = "languageFallback";
$field->required = true;
foreach ($supportedLanguages as $language) {
    $field->addOption($language, $language);
}
$form->addField($field);

$field = new Toggle();
$field->name = "languageMultiple";
$form->addField($field);

$field = new Toggle();
$field->name = "languageDefaultUser";
$field->getVisibilityCondition()->equal('languageMultiple', '1');
$form->addField($field);

$field = new Select();
$field->name = "languagesSupported";
$field->multiple = true;
$field->required = true;
$field->getVisibilityCondition()->equal('languageMultiple', '1');
foreach ($supportedLanguages as $language) {
    $field->addOption($language, $language);
}
$form->addField($field);

ModuleConfig::addForm($form, '__framelix_configuration_module_language_pagetitle__');

$form = new Form();

$field = new Select();
$field->addOption("recaptchav2", "ReCaptchaV2");
$field->addOption("recaptchav3", "ReCaptchaV3 + ReCaptchaV2");
$field->name = "captchaType";
$form->addField($field);

$field = new Toggle();
$field->name = "loginCaptcha";
$field->getVisibilityCondition()->equal('captchaType', ['recaptchav2', 'recaptchav3']);
$form->addField($field);

$field = new Text();
$field->name = "captchaKeys[recaptchav2][privateKey]";
$field->getVisibilityCondition()->equal('captchaType', ['recaptchav2', 'recaptchav3']);
$field->required = true;
$form->addField($field);

$field = new Text();
$field->name = "captchaKeys[recaptchav2][publicKey]";
$field->getVisibilityCondition()->equal('captchaType', ['recaptchav2', 'recaptchav3']);
$field->required = true;
$form->addField($field);

$field = new Text();
$field->name = "captchaKeys[recaptchav3][privateKey]";
$field->getVisibilityCondition()->equal('captchaType', 'recaptchav3');
$field->required = true;
$form->addField($field);

$field = new Text();
$field->name = "captchaKeys[recaptchav3][publicKey]";
$field->getVisibilityCondition()->equal('captchaType', 'recaptchav3');
$field->required = true;
$form->addField($field);

ModuleConfig::addForm($form, '__framelix_configuration_module_captcha_pagetitle__');

$form = new Form();


$field = new Select();
$field->name = "mailSendType";
$field->addOption('none', '__framelix_configuration_module_email_mailsendtype_none__');
$field->addOption('mail', '__framelix_configuration_module_email_mailsendtype_mail__');
$field->addOption('smtp', '__framelix_configuration_module_email_mailsendtype_smtp__');
$field->addOption('sendmail', '__framelix_configuration_module_email_mailsendtype_sendmail__');
$form->addField($field);

$field = new Text();
$field->name = "smtpHost";
$field->getVisibilityCondition()->equal('mailSendType', 'smtp');
$form->addField($field);

$field = new Number();
$field->name = "smtpPort";
$field->decimals = 0;
$field->getVisibilityCondition()->equal('mailSendType', 'smtp');
$form->addField($field);

$field = new Text();
$field->name = "smtpUsername";
$field->getVisibilityCondition()->equal('mailSendType', 'smtp');
$form->addField($field);

$field = new Password();
$field->name = "smtpPassword";
$field->getVisibilityCondition()->equal('mailSendType', 'smtp');
$form->addField($field);

$field = new Select();
$field->name = "smtpSecure";
$field->addOption('tls', "TLS");
$field->addOption('ssl', "SSL");
$field->getVisibilityCondition()->equal('mailSendType', 'smtp');
$form->addField($field);

$field = new Email();
$field->name = "emailOverrideRecipient";
$form->addField($field);

$field = new Email();
$field->name = "emailDefaultFrom";
$form->addField($field);

$field = new Text();
$field->name = "emailTitleTemplate";
$form->addField($field);

$field = new Textarea();
$field->name = "emailBodyTemplate";
$form->addField($field);

ModuleConfig::addForm($form, '__framelix_configuration_module_email_pagetitle__');

$form = new Form();

$field = new Email();
$field->name = "emailTo";
$field->required = true;
$form->addField($field);

$field = new Text();
$field->name = "emailTitle";
$field->required = true;
$form->addField($field);

$field = new Textarea();
$field->name = "emailBody";
$field->required = true;
$form->addField($field);

ModuleConfig::addForm($form, '__framelix_configuration_module_emailtext_pagetitle__', function () {
    try {
        $sendResult = \Framelix\Framelix\Utils\Email::send(
            Request::getPost('emailTitle'),
            Request::getPost('emailBody'),
            Request::getPost('emailTo')
        );
        if ($sendResult) {
            Toast::success('__framelix_config_emailtest_success__');
        } else {
            Toast::success('__framelix_config_emailtest_error__');
        }
    } catch (Throwable $e) {
        Toast::error($e->getMessage());
    }
});
$form = new Form();


$field = new Toggle();
$field->name = "errorLogExtended";
$form->addField($field);

$field = new Toggle();
$field->name = "errorLogDisk";
$form->addField($field);

$field = new Toggle();
$field->name = "errorLogSyslog";
$form->addField($field);

$field = new Email();
$field->name = "errorLogEmail";
$form->addField($field);

$field = new Html();
$field->name = "systemEventLogInfo";
$field->label = '';
$field->labelDescription = '__framelix_config_systemeventlog_labeldesc__';
$form->addField($field);

for ($i = 1; $i <= 5; $i++) {
    $field = new Toggle();
    $field->name = "systemEventLog[$i]";
    $form->addField($field);
}


ModuleConfig::addForm($form, '__framelix_configuration_module_logging_pagetitle__',
    function (Form $form) {
        $errorLogMail = Config::get('errorLogEmail');
        ModuleConfig::saveConfig($form);
        $errorLogMailNew = Request::getPost('errorLogEmail');
        Config::set('errorLogEmail', $errorLogMailNew);
        if ($errorLogMail !== $errorLogMailNew && $errorLogMailNew) {
            try {
                throw new Exception("This is a test error");
            } catch (Throwable $e) {
                ErrorHandler::sendErrorLogEmail(ErrorHandler::throwableToJson($e));
            }
        }
    });