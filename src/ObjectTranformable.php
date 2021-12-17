<?php

namespace Framelix\Framelix;

use JsonSerializable;
use Stringable;

/**
 * An interface to provide some framework object transform functions
 */
interface ObjectTranformable extends JsonSerializable, Stringable
{
    /**
     * Get the database value that is to be stored in database when calling store()
     * This is always the actual value that represent to current database value of the property
     * @return mixed
     */
    public function getDbValue(): mixed;

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string;

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string;

    /**
     * Get a value that can be used in sort functions
     * @return mixed
     */
    public function getSortableValue(): mixed;

}