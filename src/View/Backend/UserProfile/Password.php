<?php

namespace Framelix\Framelix\View\Backend\UserProfile;

use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

/**
 * Password change
 */
class Password extends View
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
     * On request
     */
    public function onRequest(): void
    {
        $this->storable = User::get();
        if (Form::isFormSubmitted("changepassword")) {
            $form = $this->getForm();
            $form->validate();
            if (!$this->storable->passwordVerify(Request::getPost('passwordNow'))) {
                Response::showFormValidationErrorResponse('__framelix_password_notcorrect__');
            }
            if (Request::getPost('passwordNew') !== Request::getPost('password2')) {
                Response::showFormValidationErrorResponse('__framelix_password_notmatch__');
            }
            $this->storable->setPassword(Request::getPost('passwordNew'));
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
        $form = $this->getForm();
        $form->addSubmitButton('save', '__framelix_save__', 'save');
        $form->show();
    }

    /**
     * Get form
     * @return Form
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->id = "changepassword";

        $field = new \Framelix\Framelix\Form\Field\Password();
        $field->name = "passwordNow";
        $field->label = "__framelix_password_current__";
        $field->minLength = 8;
        $form->addField($field);


        $field = new \Framelix\Framelix\Form\Field\Password();
        $field->name = "passwordNew";
        $field->label = "__framelix_password_new__";
        $field->minLength = 8;
        $form->addField($field);

        $field = new \Framelix\Framelix\Form\Field\Password();
        $field->name = "password2";
        $field->label = "__framelix_password_repeat__";
        $field->minLength = 8;
        $form->addField($field);

        return $form;
    }
}