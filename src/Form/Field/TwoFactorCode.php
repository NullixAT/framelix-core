<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Session;
use RobThree\Auth\TwoFactorAuth;

use function in_array;
use function strlen;

/**
 * A field to enter and validate a TOTP two-factor code
 */
class TwoFactorCode extends Field
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = '100%';

    /**
     * Auto submit the form containing this field after user has entered 6-digits
     * @var bool
     */
    public bool $formAutoSubmit = true;

    /**
     * Validation should use the secret stored in the given session key
     * @var string
     */
    public string $secretSessionName = '2fa-secret';

    /**
     * Validation should use the backup codes stored in the given session key
     * @var string|null
     */
    public ?string $backupCodesSessionName = '2fa-backup-codes';

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|bool
     */
    public function validate(): string|bool
    {
        if (!$this->isVisible()) {
            return true;
        }
        $parentValidation = parent::validate();
        if ($parentValidation !== true) {
            return $parentValidation;
        }
        $value = (string)$this->getConvertedSubmittedValue();
        $valid = false;
        $secret = Session::get($this->secretSessionName);
        if ($secret && strlen($value) === 6) {
            $tfa = new TwoFactorAuth();
            $result = $tfa->verifyCode($secret, $value);
            if ($result) {
                $valid = true;
            }
        }
        if ($this->backupCodesSessionName) {
            $backupCodes = Session::get($this->backupCodesSessionName);
            if ($backupCodes && strlen($value) === 10) {
                if (in_array($value, $backupCodes, true)) {
                    $valid = true;
                }
            }
        }
        if (!$valid) {
            return Lang::get('__framelix_form_validation_twofactor__');
        }
        return true;
    }
}