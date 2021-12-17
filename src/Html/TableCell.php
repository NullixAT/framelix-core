<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Url;
use JsonSerializable;

/**
 * Html Table Cell
 * Used to show some special contents
 * Default values like strings should be used natively without this class
 */
class TableCell implements JsonSerializable
{
    /**
     * String value
     * @var mixed
     */
    public mixed $stringValue = null;

    /**
     * Sort value
     * @var mixed
     */
    public mixed $sortValue = null;

    /**
     * Icon, will replace the stringValue
     * @var string|null
     */
    public ?string $icon = null;


    /**
     * Icon color, a class or hex code
     * @var string|null
     */
    public ?string $iconColor = null;

    /**
     * Icon tooltip
     * @var string|null
     */
    public ?string $iconTooltip = null;

    /**
     * Icon url to redirect on click
     * @var Url|string|null
     */
    public Url|string|null $iconUrl = null;

    /**
     * Icon action to handle in javascript
     * @var string|null
     */
    public ?string $iconAction = null;

    /**
     * Open the icon url in a new tab
     * @var bool
     */
    public bool $iconUrlBlank = true;

    /**
     * Additional icon attributes
     * @var HtmlAttributes|null
     */
    public ?HtmlAttributes $iconAttributes = null;

    /**
     * Json serialize
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['properties' => (array)$this];
    }
}