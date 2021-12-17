<?php

namespace Framelix\Framelix\Form\Field;

/**
 * An IBAN field - International Bank Account Number
 */
class Iban extends Text
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 300;

}