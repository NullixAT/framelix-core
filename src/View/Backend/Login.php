<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Html;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\ColorName;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Storable\UserWebAuthn;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\UserProfile\Fido2;
use lbuchs\WebAuthn\Binary\ByteBuffer;

use function base64_decode;

/**
 * User login page
 */
class Login extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'webauthn-getargs':
                $webAuthn = Fido2::getWebAuthnInstance();
                $user = User::getByEmail((string)$jsCall->parameters['email']);
                $userWebAuthns = $user ? UserWebAuthn::getByCondition('user = {0}', [$user]) : null;
                if (!$user || !$userWebAuthns) {
                    $jsCall->result = '__framelix_login_invalid_fido2__';
                    return;
                }
                $credentialIds = [];
                foreach ($userWebAuthns as $userWebAuthn) {
                    $credentialId = $userWebAuthn->authData['credentialId'] ?? null;
                    if ($credentialId) {
                        $credentialIds[] = base64_decode($credentialId);
                    }
                }
                $jsCall->result = ['getArgs' => (array)$webAuthn->getGetArgs($credentialIds)];
                Session::set('fido2-login-challenge', (string)$webAuthn->getChallenge());
                break;
            case 'webauthn-login':
                $webAuthn = Fido2::getWebAuthnInstance();
                $user = User::getByEmail((string)($jsCall->parameters['formValues']['email'] ?? null));
                $userWebAuthns = $user ? UserWebAuthn::getByCondition('user = {0}', [$user]) : null;
                if (!$user || !$userWebAuthns) {
                    $jsCall->result = '__framelix_login_invalid_fido2__';
                    return;
                }
                $data = null;
                foreach ($userWebAuthns as $userWebAuthn) {
                    $credentialId = $userWebAuthn->authData['credentialId'] ?? null;
                    if (!$credentialId) {
                        continue;
                    }
                    if ($credentialId === $jsCall->parameters['credentialId']) {
                        $data = $webAuthn->processGet(
                            base64_decode($jsCall->parameters["clientData"]),
                            base64_decode($jsCall->parameters["authenticatorData"]),
                            base64_decode($jsCall->parameters["signature"]),
                            $userWebAuthn->authData['credentialPublicKey'],
                            ByteBuffer::fromHex(Session::get('fido2-login-challenge')),
                        );
                        break;
                    }
                }

                if (!$data) {
                    $jsCall->result = '__framelix_view_backend_userprofile_fido2_webauthn_error__';
                    return;
                }
                $token = UserToken::create($user);
                UserToken::setCookieValue(
                    $token->token,
                    ($jsCall->parameters['formValues']['stay'] ?? null) ? 60 * 86400 : null
                );
                // create system event logs
                $logCategory = SystemEventLog::CATEGORY_LOGIN_SUCCESS;
                if (Config::get('systemEventLog[' . $logCategory . ']')) {
                    SystemEventLog::create($logCategory, null, ['email' => $user->email]);
                }
                $url = Url::getBrowserUrl();
                $jsCall->result = ['url' => $url->getParameter('redirect') ?? Url::getApplicationUrl()];
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (User::get()) {
            if (Request::getGet('redirect')) {
                Url::create(Request::getGet('redirect'))->redirect();
            }
            if (Config::get('backendDefaultView')) {
                \Framelix\Framelix\View::getUrl(Config::get('backendDefaultView'))->redirect();
            }
            Url::getApplicationUrl()->redirect();
        }
        if (Form::isFormSubmitted('login')) {
            $form = $this->getForm();
            $form->validate();
            $email = (string)Request::getPost('email');
            $user = User::getByEmail($email);
            if (BruteForceProtection::isBlocked('backend-login')) {
                Url::getBrowserUrl()->redirect();
            }
            BruteForceProtection::countUp('backend-login');
            if ($user && $user->passwordVerify(Request::getPost('password'))) {
                if ($user->twoFactorSecret) {
                    // if 2fa, redirect to 2fa verify page
                    Session::set('2fa-user', $user->id);
                    Session::set('2fa-user-stay', Request::getPost('stay'));
                    Session::set('2fa-secret', $user->twoFactorSecret);
                    Session::set('2fa-backup-codes', $user->twoFactorBackupCodes);
                    \Framelix\Framelix\View::getUrl(Login2FA::class)->setParameter(
                        'redirect',
                        Request::getGet('redirect')
                    )->redirect();
                }

                $token = UserToken::create($user);
                UserToken::setCookieValue($token->token, Request::getPost('stay') ? 60 * 86400 : null);
                // create system event logs
                $logCategory = SystemEventLog::CATEGORY_LOGIN_SUCCESS;
                if (Config::get('systemEventLog[' . $logCategory . ']')) {
                    SystemEventLog::create($logCategory, null, ['email' => $email]);
                }
                BruteForceProtection::reset('backend-login');
                if (Request::getGet('redirect')) {
                    Url::create(Request::getGet('redirect'))->redirect();
                }
                Url::getApplicationUrl()->redirect();
            } else {
                // create system event logs
                $logCategory = SystemEventLog::CATEGORY_LOGIN_FAILED;
                if (Config::get('systemEventLog[' . $logCategory . ']')) {
                    SystemEventLog::create($logCategory, null, ['email' => $email]);
                }
                Response::showFormValidationErrorResponse('__framelix_login_invalid_user__');
            }
        }
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $form = $this->getForm();
        $form->addSubmitButton('login', '__framelix_login_submit__', 'login');
        $form->addButton('fido2', '__framelix_login_fido2__', buttonColor: ColorName::PRIMARY);
        $form->show();
        ?>
        <style>
          .framelix-button:not(.fido2-enabled)[data-action='fido2'] {
            display: none;
          }
        </style>
        <script>
          (async function () {
            const form = FramelixForm.getById('<?=$form->id?>')
            await form.rendered
            form.container.on('change', function () {
              const email = form.fields['email'].getValue()
              FramelixLocalStorage.set('login-email', email)
            })
            const storedEmail = FramelixLocalStorage.get('login-email')
            if (storedEmail) {
              form.fields['email'].setValue(storedEmail)
              form.fields['password'].container.find('input').trigger('focus')
            } else {
              form.fields['email'].container.find('input').trigger('focus')
            }
            if (FramelixLocalStorage.get('webauthn')) {
              const fidoButton = $('.framelix-button[data-action=\'fido2\']')
              fidoButton.addClass('fido2-enabled')
              fidoButton.on('click', async function () {
                let getArgsServerData = await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                    __CLASS__,
                    'webauthn-getargs'
                )?>', form.getValues())
                if (typeof getArgsServerData === 'string') {
                  FramelixToast.error(getArgsServerData)
                  return
                }
                let getArgs = getArgsServerData.getArgs
                Framelix.recursiveBase64StrToArrayBuffer(getArgs)
                navigator.credentials.get(getArgs).then(async function (getArgsClientData) {
                  let loginArgsParams = {
                    'formValues': form?.getValues(),
                    'credentialId': Framelix.arrayBufferToBase64(getArgsClientData.rawId),
                    'clientData': Framelix.arrayBufferToBase64(getArgsClientData.response.clientDataJSON),
                    'authenticatorData': Framelix.arrayBufferToBase64(getArgsClientData.response.authenticatorData),
                    'signature': Framelix.arrayBufferToBase64(getArgsClientData.response.signature),
                  }
                  let loginResult = await FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                      __CLASS__,
                      'webauthn-login'
                  )?>', loginArgsParams)
                  if (loginResult.url) {
                    Framelix.redirect(loginResult.url)
                  } else {
                    FramelixToast.error(loginResult)
                  }
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
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "login";
        $form->submitWithEnter = true;

        $field = new Email();
        $field->name = "email";
        $field->label = "__framelix_email__";
        $field->required = true;
        $field->maxWidth = null;
        $form->addField($field);

        $field = new Password();
        $field->name = "password";
        $field->label = "__framelix_password__";
        $field->maxWidth = null;
        $field->required = true;
        $form->addField($field);

        if (Config::get('loginCaptcha')) {
            $field = new Captcha();
            $field->name = "captcha";
            $field->required = true;
            $field->trackingAction = "framelix_backend_login";
            $form->addField($field);
        }

        $field = new Toggle();
        $field->name = "stay";
        $field->label = "__framelix_stay_logged_in__";
        $form->addField($field);

        if (\Framelix\Framelix\Utils\Email::isAvailable()) {
            $field = new Html();
            $field->name = "forgot";
            $field->defaultValue = '<a href="' . \Framelix\Framelix\View::getUrl(
                    ForgotPassword::class
                ) . '">' . Lang::get('__framelix_forgotpassword__') . '</a>';
            $form->addField($field);
        }

        return $form;
    }
}