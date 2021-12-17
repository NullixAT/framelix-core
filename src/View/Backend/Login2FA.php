<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\TwoFactorCode;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Storable\BruteForceProtection;
use Framelix\Framelix\Storable\SystemEventLog;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;

use function array_values;
use function strlen;

/**
 * User login page for 2fa
 */
class Login2FA extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * The 2fa user
     * @var User|null
     */
    protected ?User $user = null;

    /**
     * On request
     */
    public function onRequest(): void
    {
        if (User::get()) {
            Url::getApplicationUrl()->redirect();
        }
        $this->user = User::getById(Session::get('2fa-user'));
        if (!$this->user || $this->user->twoFactorSecret !== Session::get('2fa-secret')) {
            \Framelix\Framelix\View::getUrl(Login::class)->redirect();
        }
        if (Form::isFormSubmitted('twofa')) {
            $form = $this->getForm();
            $form->validate();

            $token = UserToken::create($this->user);
            UserToken::setCookieValue($token->token, Session::get('2fa-user-stay') ? 60 * 86400 : null);

            // create system event logs
            $logCategory = SystemEventLog::CATEGORY_LOGIN_SUCCESS;
            if (Config::get('systemEventLog[' . $logCategory . ']')) {
                SystemEventLog::create($logCategory, null, ['email' => $this->user->email]);
            }

            $code = Request::getPost('code');
            if (strlen($code) === 10 && $this->user->twoFactorBackupCodes) {
                Toast::warning('__framelix_form_2fa_backup_code_used__');
                $backupCodes = $this->user->twoFactorBackupCodes;
                foreach ($backupCodes as $key => $backupCode) {
                    if ($backupCode === $code) {
                        unset($backupCodes[$key]);
                        break;
                    }
                }
                $this->user->twoFactorBackupCodes = array_values($backupCodes);
                $this->user->store();
            }
            BruteForceProtection::reset('backend-login');
            (Request::getGet('redirect') ? Url::create(Request::getGet('redirect')) : Url::getApplicationUrl(
            ))->redirect();
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
        $form = $this->getForm();
        $form->show();
        ?>
        <a href="<?= \Framelix\Framelix\View::getUrl(Login::class)->setParameter(
            'redirect',
            Request::getGet('redirect')
        ) ?>"><?= Lang::get('__framelix_view_backend_login2fa_back__') ?></a>
        <?php
    }


    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "twofa";

        $field = new TwoFactorCode();
        $field->name = "code";
        $form->addField($field);

        return $form;
    }
}