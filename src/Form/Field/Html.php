<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Utils\StringUtils;

/**
 * A html field. Not a real input field, just to provide a case to integrate any html into a form
 */
class Html extends Field
{
    /**
     * Validate
     * A html field contains nothing to be submitted
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * Get submitted value
     * @return string|null
     */
    public function getSubmittedValue(): ?string
    {
        return null;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        if (isset($this->defaultValue)) {
            $data['properties']['defaultValue'] = StringUtils::stringify(
                $this->defaultValue,
                '<br/>',
                ["getHtmlString"]
            );
        }
        return $data;
    }
}