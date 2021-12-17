<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\UserVerificationToken;
use Framelix\Framelix\View\Backend\View;

use function in_array;

/**
 * Email change verification
 */
class EmailVerification extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = true;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->layout = self::LAYOUT_SMALL_CENTERED;
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        if ($tokenStr = Request::getGet('token')) {
            $token = UserVerificationToken::getForToken((string)$tokenStr);
            $validCategories = [
                UserVerificationToken::CATEGORY_CHANGE_EMAIL_OLD,
                UserVerificationToken::CATEGORY_CHANGE_EMAIL_NEW
            ];
            if ($token && in_array($token->category, $validCategories)) {
                $emaiNew = $token->additionalData;
                $user = $token->user;
                $token->delete();
                $step = 2;
                $hasOtherToken = UserVerificationToken::getByConditionOne(
                    'user = {0} && category IN {1}',
                    [$user, $validCategories]
                );
                if ($hasOtherToken) {
                    $step = 1;
                } else {
                    $user->email = $emaiNew;
                    $user->store();
                }
                echo '<div class="framelix-alert framelix-alert-success">' . Lang::get(
                        '__framelix_view_backend_userprofile_emailverification_step' . $step . '__',
                        [$emaiNew]
                    ) . '</div>';
                return;
            }
        }

        echo Lang::get('__framelix_view_backend_userprofile_emailverification_error__');
    }
}