<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\DateTime;
use Framelix\Framelix\Form\Field\Captcha;
use Framelix\Framelix\Form\Field\Email;
use Framelix\Framelix\Form\Field\Password;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserVerificationToken;
use Framelix\Framelix\Url;

/**
 * ForgotPassword
 */
class ForgotPassword extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * User verification token
     * @var UserVerificationToken|null
     */
    private ?UserVerificationToken $token = null;

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (User::get()) {
            Url::getApplicationUrl()->redirect();
        }
        if ($tokenStr = Request::getGet('token')) {
            $this->token = UserVerificationToken::getForToken((string)$tokenStr);
            if ($this->token && $this->token->createTime < DateTime::create("now - 1 hour")) {
                $this->token->delete(true);
                $this->token = null;
            }
            if (!$this->token) {
                Toast::error('__framelix_view_backend_forgotpassword_token_invalid__');
            }
        }
        if ($this->token && Form::isFormSubmitted('reset')) {
            $form = $this->getFormNewPassword();
            $form->validate();
            if (Request::getPost('password') !== Request::getPost('password2')) {
                Response::showFormValidationErrorResponse('__framelix_password_notmatch__');
            }
            $this->token->user->setPassword(Request::getPost('password'));
            $this->token->user->store();
            Toast::success('__framelix_view_backend_forgotpassword_resetdone__');
            $this->token->delete(true);
            \Framelix\Framelix\View::getUrl(Login::class)->redirect();
        }
        if (Form::isFormSubmitted('forgot')) {
            $form = $this->getFormSendMail();
            $form->validate();
            $email = (string)Request::getPost('email');
            $user = User::getByEmail($email);
            if ($user) {
                $verificationToken = UserVerificationToken::create(
                    $user,
                    UserVerificationToken::CATEGORY_FORGOT_PASSWORD
                );
                $url = $this->getSelfUrl();
                $url->setParameter('token', $verificationToken->token);
                $body = Lang::get(
                    '__framelix_view_backend_forgotpassword_mailbody__',
                    [Url::getApplicationUrl()->getUrlAsString()]
                );
                $body .= "<br/><br/>";
                $body .= '<a href="' . $url . '">' . $url . '</a>';
                \Framelix\Framelix\Utils\Email::send(
                    '__framelix_view_backend_forgotpassword__',
                    $body,
                    $user
                );
            }
            Toast::success(Lang::get('__framelix_view_backend_forgotpassword_sentmail__', [$email]));
            Url::getBrowserUrl()->redirect();
            return;
        }
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if ($this->token) {
            $form = $this->getFormNewPassword();
            $form->addSubmitButton('reset', '__save__', 'login');
        } else {
            $form = $this->getFormSendMail();
            $form->addSubmitButton('send', '__framelix_view_backend_forgotpassword_sendmail__', 'login');
        }
        $form->show();
    }


    /**
     * Get form to reset password
     * @return Form
     */
    public function getFormNewPassword(): Form
    {
        $form = new Form();
        $form->id = "reset";
        $form->submitWithEnter = true;

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