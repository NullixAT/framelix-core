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