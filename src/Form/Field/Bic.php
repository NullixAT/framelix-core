<?php

namespace Framelix\Framelix\Form\Field;

/**
 * A BIC field (Bank Identifier Code)
 */
class Bic extends Text
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 200;
}