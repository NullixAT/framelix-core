<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserWebAuthn;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use Throwable;

use function base64_decode;
use function base64_encode;
use function json_decode;
use function json_encode;
use function substr;

/**
 * Fido2
 */
class Fido2 extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * The storable
     * @var UserWebAuthn
     */
    private UserWebAuthn $storable;

    /**
     * The storable meta
     * @var \Framelix\Framelix\StorableMeta\UserWebAuthn
     */
    private \Framelix\Framelix\StorableMeta\UserWebAuthn $meta;

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'createargs':
                $webAuthn = self::getWebAuthnInstance();
                $user = User::get();
                $createArgs = $webAuthn->getCreateArgs(
                    $user->id,
                    $user->email,
                    $user->email
                );
                Session::set('fido2-create-challenge', (string)$webAuthn->getChallenge());
                $jsCall->result = ['createArgs' => (array)$createArgs];
                break;
            case 'processargs':
                $user = User::get();
                $webAuthn = self::getWebAuthnInstance();
                try {
                    $data = $webAuthn->processCreate(
                        base64_decode($jsCall->parameters["clientData"]),
                        base64_decode($jsCall->parameters["attestationObject"]),
                        ByteBuffer::fromHex(Session::get('fido2-create-challenge'))
                    );
                    $data->credentialId = base64_encode($data->credentialId);
                    $data->AAGUID = base64_encode($data->AAGUID);
                    $data = json_decode(json_encode($data), true);
                    $userWebAuthn = new UserWebAuthn();
                    $userWebAuthn->deviceName = substr($jsCall->parameters['deviceName'] ?? "", 0, 191);
                    $userWebAuthn->user = $user;
                    $userWebAuthn->authData = $data;
                    $userWebAuthn->store();
                    $jsCall->result = true;
                    Toast::success('__framelix_view_backend_userprofile_fido2_success__');
                } catch (Throwable $e) {
                    $jsCall->result = Lang::get(
                            '__framelix_view_backend_userprofile_fido2_webauthn_error__'
                        ) . ": " . $e->getMessage();
                }
                break;
        }
    }

    /**
     * Get web authn instance
     * @return WebAuthn
     */
    public static function getWebAuthnInstance(): WebAuthn
    {
        require __DIR__ . "/../../../../vendor/webauthn/src/WebAuthn.php";
        return new WebAuthn(
            $_SERVER['HTTP_HOST'] ?? "0x.at",
            $_SERVER['HTTP_HOST'] ?? "0x.at",
            ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'none', 'packed', 'tpm']
        );
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = UserWebAuthn::getByIdOrNew(Request::getGet('editWebAuthn'));
        if ($this->storable && $this->storable->user !== User::get()) {
            $this->storable = new UserWebAuthn();
        }
        if (!$this->storable->id) {
            $this->storable->user = User::get();
        }
        $this->meta = new \Framelix\Framelix\StorableMeta\UserWebAuthn($this->storable);
        if (Session::get(__CLASS__ . "-pw-verified")) {
            if (Form::isFormSubmitted($this->meta->getEditFormId())) {
                $form = $this->meta->getEditForm();
                $form->validate();
                $form->setStorableValues($this->storable);
                $this->storable->store();
                Toast::success('__framelix_saved__');
                Url::getBrowserUrl()->redirect();
            }
        } elseif (Form::isFormSubmitted('pw-verify')) {
            if (!$this->storable->user->passwordVerify(Request::getPost('password'))) {
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
        if (!Config::get('applicationHttps')) {
            ?>
            <p class="framelix-alert framelix-alert-error"><?= Lang::get('__framelix_https_required__') ?></p>
            <?php
            return;
        }
        if (!Session::get(__CLASS__ . "-pw-verified")) {
            $form = $this->getPasswordVerifyForm();
            $form->addSubmitButton('verify', '__framelix_goahead__', 'lock_open');
            $form->show();
            return;
        }
        ?>
        <p class="framelix-alert"><?= Lang::get('__framelix_view_backend_userprofile_fido2_info__') ?></p>
        <p class="framelix-alert"><?= Lang::get('__framelix_view_backend_userprofile_fido2_2fa_info__') ?></p>
        <?php
        $form = $this->meta->getEditForm();
        if (!$this->storable->id) {
            $form->buttons = [];
            $form->addButton('enable', '__framelix_view_backend_userprofile_fido2_enable__', buttonColor: 'success');
        }
        $form->show();

        $authns = UserWebAuthn::getByCondition('user = {0}', [User::get()]);
        if ($authns) {
            $this->meta->getTable($authns)->show();
        }

        ?>
        <script>
          (async function () {
            const form = FramelixForm.getById('<?=$form->id?>')
            await form.rendered
            const enableBtn = form.container.find('.framelix-button[data-action=\'enable\']')
            if (FramelixLocalStorage.get('webauthn')) {
              const disableBtn = $(`<button class="framelix-button framelix-button-primary">${FramelixLang.get('__framelix_view_backend_userprofile_fido2_disable__')}</button>`)
              enableBtn.after(disableBtn)
              enableBtn.after(`<div class="framelix-alert framelix-alert-success">${FramelixLang.get('__framelix_view_backend_userprofile_fido2_already_enabled__')}</div>`)
              enableBtn.remove()
              disableBtn.on('click', function () {
                FramelixLocalStorage.remove('webauthn')
                window.location.reload()
              })
            } else {
              enableBtn.on('click', async function () {
                if (typeof navigator.credentials === 'undefined' || typeof navigator.credentials.create === 'undefined') {
                  FramelixToast.error('__framelix_view_backend_userprofile_fido2_unsupported__')
                  return
                }
                if (!await form.validate()) return

                let createArgsServerData = await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                    __CLASS__,
                    'createargs'
                )?>')
                let createArgs = createArgsServerData.createArgs
                Framelix.recursiveBase64StrToArrayBuffer(createArgs)
                navigator.credentials.create(createArgs).then(async function (createArgsClientData) {
                  if (!createArgsClientData) {
                    FramelixToast.error('__framelix_view_backend_userprofile_fido2_error__')
                    return
                  }
                  const values = form.getValues()
                  let processArgsParams = {
                    'deviceName': values.deviceName,
                    'clientData': Framelix.arrayBufferToBase64(createArgsClientData.response.clientDataJSON),
                    'attestationObject': Framelix.arrayBufferToBase64(createArgsClientData.response.attestationObject)
                  }
                  let processArgsResult = await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                      __CLASS__,
                      'processargs'
                  )?>', processArgsParams)
                  if (processArgsResult === true) {
                    FramelixLocalStorage.set('webauthn', true)
                    Framelix.redirect(window.location.href)
                  } else {
                    FramelixToast.error(processArgsResult)
                  }
                }).catch(function (e) {
                  FramelixToast.error(FramelixLang.get('__framelix_view_backend_userprofile_fido2_error__', [e.message]), -1)
                })
              })
            }
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