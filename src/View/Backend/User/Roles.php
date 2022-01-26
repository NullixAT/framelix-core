<?php

namespace Framelix\Framelix\View\Backend\User;

use Framelix\Framelix\Config;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Form\Form;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\View\Backend\View;

/**
 * Roles
 */
class Roles extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin";

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
        $this->storable = User::getByIdOrNew(Request::getGet('id'));
        if (!$this->storable->id) {
            $this->showInvalidUrlError();
        }
        if (Form::isFormSubmitted("roles")) {
            $form = $this->getForm();
            $form->validate();
            $roles = Config::get('userRoles');
            foreach ($roles as $role => $label) {
                if (Request::getPost("role[$role]")) {
                    $this->storable->addRole($role);
                } else {
                    $this->storable->removeRole($role);
                }
            }
            // check if at least one admin exist
            $admins = User::getByCondition("JSON_CONTAINS(roles,'\"admin\"','$') && id != " . $this->storable);
            if (!$admins && !User::hasRole("admin", $this->storable)) {
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
        $form->id = "roles";

        $roles = Config::get('userRoles');
        foreach ($roles as $role => $label) {
            $field = new Toggle();
            $field->name = "role[$role]";
            $field->label = $label;
            $field->defaultValue = User::hasRole($role, $this->storable);
            $form->addField($field);
        }

        return $form;
    }
}