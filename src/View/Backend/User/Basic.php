<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

/**
 * Basic data management for a user
 */
class Basic extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,usermanagement";

    /**
     * The storable
     * @var User
     */
    private User $storable;

    /**
     * The storable meta
     * @var \Framelix\Framelix\StorableMeta\User
     */
    private \Framelix\Framelix\StorableMeta\User $meta;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = User::getByIdOrNew(Request::getGet('id'));
        $this->meta = new \Framelix\Framelix\StorableMeta\User($this->storable);
        if (Form::isFormSubmitted($this->meta->getEditFormId())) {
            $form = $this->meta->getEditForm();
            $form->validate();
            $form->setStorableValues($this->storable);
            if (User::hasRole("admin") && $this->storable->flagLocked) {
                Response::showFormValidationErrorResponse('__framelix_user_edituser_validation_adminrequired__');
            }
            $this->storable->store();
            Toast::success('__framelix_saved__');
            Url::getBrowserUrl()->setParameter('id', $this->storable)->redirect();
        }
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $form = $this->meta->getEditForm();
        $form->show();
    }
}