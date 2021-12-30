<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View\Backend\View;
use RobThree\Auth\TwoFactorAuth;

use function implode;

use const FRAMELIX_MODULE;

/**
 * TwoFactor
 */
class TwoFactor extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * The storable
     * @var User
     */
    private User $storable;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        if (!Session::get(__CLASS__ . "-pw-verified")) {
            return;
        }
        switch ($jsCall->action) {
            case 'enable':
                $tfa = new TwoFactorAuth($jsCall->parameters['name']);
                $secret = $tfa->createSecret();
                $codes = [];
                for ($i = 1; $i <= 10; $i++) {
                    $codes[] = RandomGenerator::getRandomString(
                        10,
                        null,
                        RandomGenerator::CHARSET_ALPHANUMERIC_READABILITY
                    );
                }
                Session::set('2fa-secret', $secret);
                Session::set('2fa-backup-codes', $codes);
                ?>
                <div style="text-align: center">
                    <div><?= Lang::get('__framelix_view_backend_userprofile_2fa_enable_info__') ?></div>
                    <div class="framelix-spacer-x2"></div>
                    <button class="framelix-button framelix-button-primary"
                            data-action="getcodes"><?= Lang::get(
                            '__framelix_view_backend_userprofile_2fa_download_codes__'
                        ) ?></button>
                    <div class="framelix-spacer-x2"></div>
                    <div id="qrcode"></div>
                    <div class="framelix-spacer-x2"></div>
                    <div><?= Lang::get('__framelix_view_backend_userprofile_2fa_enable_enter__') ?></div>
                    <div class="framelix-spacer-x2"></div>
                    <?php
                    $form = self::getEnableForm();
                    $form->show();
                    ?>
                </div>
                <script>
                  (async function () {
                    const container = $('#qrcode')
                    new QRCode(container[0], {
                      text: <?=JsonUtils::encode($tfa->getQRText(User::get()->email, $secret))?>,
                      width: Math.min(container.width(), 600),
                      height: Math.min(container.width(), 600),
                      colorDark: '#000',
                      colorLight: '#fff',
                      correctLevel: QRCode.CorrectLevel.H
                    })
                    setTimeout(function () {
                      container.find('img').removeAttr('style').css('max-width', '100%')
                    }, 10)
                  })()
                </script>
                <?php
                break;
            case 'test':
                ?>
                <div style="text-align: center">
                    <?php
                    $form = self::getTestForm();
                    $form->show();
                    ?>
                </div>
                <?php
                break;
            case 'disable':
                $user = User::get();
                $user->twoFactorSecret = null;
                $user->twoFactorBackupCodes = null;
                $user->store();
                Toast::success('__framelix_view_backend_userprofile_2fa_disabled__');
                Url::getBrowserUrl()->redirect();
            case 'getcodes':
                Response::download("@" . implode("\n", Session::get('2fa-backup-codes')), "backup-codes.txt");
            case 'regenerate':
                $codes = [];
                for ($i = 1; $i <= 10; $i++) {
                    $codes[] = RandomGenerator::getRandomString(
                        10,
                        null,
                        RandomGenerator::CHARSET_ALPHANUMERIC_READABILITY
                    );
                }
                $user = User::get();
                $user->twoFactorBackupCodes = $codes;
                $user->store();
                Response::download("@" . implode("\n", $codes), "backup-codes.txt");
        }
    }

    /**
     * Get form
     * @return Form
     */
    public static function getEnableForm(): Form
    {
        $form = new Form();
        $form->id = "twofa-enable";
        $form->submitUrl = \Framelix\Framelix\View::getUrl(TwoFactor::class);

        $field = new TwoFactorCode();
        $field->name = "code";
        $form->addField($field);

        return $form;
    }

    /**
     * Get form
     * @return Form
     */
    public static function getTestForm(): Form
    {
        $form = new Form();
        $form->id = "twofa-test";
        $form->submitUrl = \Framelix\Framelix\View::getUrl(TwoFactor::class);

        $field = new TwoFactorCode();
        $field->name = "code";
        $field->backupCodesSessionName = null;
        $form->addField($field);

        return $form;
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = User::get();
        if (Session::get(__CLASS__ . "-pw-verified")) {
            if (Form::isFormSubmitted('twofa-enable')) {
                $form = self::getEnableForm();
                $form->validate();
                $this->storable->twoFactorSecret = Session::get('2fa-secret');
                $this->storable->twoFactorBackupCodes = Session::get('2fa-backup-codes');
                $this->storable->store();
                Session::set('2fa-secret', null);
                Session::set('2fa-backup-codes', null);
                Toast::success('__framelix_view_backend_userprofile_2fa_enabled__');
                Url::getBrowserUrl()->redirect();
            }
            if (Form::isFormSubmitted('twofa-test')) {
                Session::set('2fa-backup-codes', $this->storable->twoFactorBackupCodes);
                $form = self::getEnableForm();
                $form->validate();

                $code = Request::getPost('code');
                if (strlen($code) === 10 && $this->storable->twoFactorBackupCodes) {
                    $backupCodes = $this->storable->twoFactorBackupCodes;
                    foreach ($backupCodes as $key => $backupCode) {
                        if ($backupCode === $code) {
                            unset($backupCodes[$key]);
                            break;
                        }
                    }
                    $this->storable->twoFactorBackupCodes = array_values($backupCodes);
                    $this->storable->store();
                    Toast::success('__framelix_form_2fa_backup_code_used__');
                } else {
                    Toast::success('__framelix_view_backend_userprofile_2fa_test_success__');
                }
                Url::getBrowserUrl()->redirect();
            }
        } elseif (Form::isFormSubmitted('pw-verify')) {
            if (!$this->storable->passwordVerify(Request::getPost('password'))) {
                Response::showFormValidationErrorResponse(['password' => '__framelix_password_incorrect__']);
            }
            Session::set(__CLASS__ . "-pw-verified", true);
            Url::getBrowserUrl()->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if (!Session::get(__CLASS__ . "-pw-verified")) {
            $form = $this->getPasswordVerifyForm();
            $form->addSubmitButton('verify', '__framelix_goahead__', 'lock_open');
            $form->show();
            return;
        }
        ?>
        <div class="framelix-alert"><?= Lang::get('__framelix_view_backend_userprofile_2fa_info__') ?></div>
        <div class="framelix-spacer"></div>
        <?php
        if ($this->storable->twoFactorSecret) {
            ?>
            <div class="framelix-alert"><?= Lang::get('__framelix_view_backend_userprofile_2fa_disable_info__') ?></div>
            <div class="framelix-spacer"></div>
            <button class="framelix-button framelix-button-success"
                    data-action="disable"
                    data-icon-left="power_off"><?= Lang::get(
                    '__framelix_view_backend_userprofile_2fa_disable__'
                ) ?></button>
            <br/>
            <button class="framelix-button framelix-button-primary"
                    data-action="test"
                    data-icon-left="bug_report"><?= Lang::get(
                    '__framelix_view_backend_userprofile_2fa_test__'
                ) ?></button>
            <br/>
            <button class="framelix-button framelix-button-error"
                    data-action="regenerate"
                    data-icon-left="emergency"><?= Lang::get(
                    '__framelix_view_backend_userprofile_2fa_regenerate_codes__'
                ) ?></button>
            <?php
        } else {
            ?>
            <button class="framelix-button framelix-button-success"
                    data-action="enable"><?= Lang::get('__framelix_view_backend_userprofile_2fa_enable__') ?></button>
            <?php
        }
        ?>
        <script>
          (function () {
            $(document).on('click', '.framelix-button[data-action]', async function () {
              switch ($(this).attr('data-action')) {
                case 'enable':
                  await FramelixDom.includeCompiledFile('Framelix', 'js', 'qrcodejs', 'QRCode')
                  let name = await FramelixModal.prompt('__framelix_view_backend_userprofile_2fa_enable_name__', '<?=FRAMELIX_MODULE?>').promptResult
                  Framelix.showProgressBar(1)
                  await FramelixModal.callPhpMethod('<?=JsCall::getCallUrl(
                      __CLASS__,
                      'enable'
                  )?>', { 'name': name })
                  Framelix.showProgressBar()
                  break
                case 'test':
                  await FramelixModal.callPhpMethod('<?=JsCall::getCallUrl(
                      __CLASS__,
                      'test'
                  )?>')
                  break
                case 'disable':
                  if (await FramelixModal.confirm('__framelix_view_backend_userprofile_2fa_disable_warning__').confirmed) {
                    await FramelixModal.callPhpMethod('<?=JsCall::getCallUrl(
                        __CLASS__,
                        'disable'
                    )?>')
                  }
                  break
                case 'getcodes':
                  await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                      __CLASS__,
                      'getcodes'
                  )?>')
                  break
                case 'regenerate':
                  if (await FramelixModal.confirm('__framelix_view_backend_userprofile_2fa_regenerate_codes_warning__').confirmed) {
                    await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                        __CLASS__,
                        'regenerate'
                    )?>')
                  }
                  break
              }
            })
          })()
        </script>
        <?php
    }

    /**
     * Get form
     * @return Form
     */
    public function getPasswordVerifyForm(): Form
    {
        $form = new Form();
        $form->id = "pw-verify";

        $field = new \Framelix\Framelix\Form\Field\Password();
        $field->name = "password";
        $field->label = '__framelix_password_verify__';
        $form->addField($field);

        return $form;
    }
}