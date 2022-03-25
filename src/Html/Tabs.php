<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View;
use JsonSerializable;

use function get_class;

/**
 * Tabs - Show content in multiple tabs
 */
class Tabs implements JsonSerializable
{
    /**
     * The tabs
     * @var array
     */
    private array $tabs = [];

    /**
     * Constructor
     * @param string $id
     */
    public function __construct(
        public string $id = "tabs"
    ) {
    }

    /**
     * Add a tab
     * @param string $id The tab internal id
     * @param string|null $label The label, if null then use the page title if a view is given in $content
     * @param View|Url|string $content If view is passed, it will be loaded with ajax
     * @param array|null $urlParameters Url parameters to append to the View/Url async call
     * @param string|null $tabColor Tab color
     * @param string|null $accessRole The access role to check before adding this tab. If null then use View's default access role
     * @param HtmlAttributes|null $optionalButtonAttributes Add optional button attributes
     * @param HtmlAttributes|null $optionalContentAttributes Add optional content container attributes
     */
    public function addTab(
        string $id,
        ?string $label,
        View|Url|string $content,
        array $urlParameters = null,
        ?string $tabColor = null,
        ?string $accessRole = null,
        HtmlAttributes|null $optionalButtonAttributes = null,
        HtmlAttributes|null $optionalContentAttributes = null
    ): void {
        if ($label === null && $content instanceof View) {
            $meta = View::getMetadataForView($content);
            if ($accessRole === null) {
                $accessRole = View::replaceAccessRoleParameters($meta['accessRole'], View::getUrl(get_class($content)));
            }
            if (!User::hasRole($accessRole)) {
                return;
            }
            $label = View::getTranslatedPageTitle(get_class($content), true);
        }
        $this->tabs[$id] = [
            "id" => $id,
            "label" => $label,
            "content" => $content,
            "urlParameters" => $urlParameters,
            "tabColor" => $tabColor,
            "optionalButtonAttributes" => $optionalButtonAttributes,
            "optionalContentAttributes" => $optionalContentAttributes
        ];
    }

    /**
     * Show tabs
     */
    public function show(): void
    {
        $jsonData = JsonUtils::encode($this);
        $randomId = RandomGenerator::getRandomHtmlId();
        ?>
        <div id="<?= $randomId ?>"></div>
        <script>
          (function () {
            const tabs = FramelixTabs.createFromPhpData(<?=$jsonData?>)
            $("#<?=$randomId?>").replaceWith(tabs.container)
            tabs.render()
          })()
        </script>
        <?php
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $properties = [];
        foreach ($this as $key => $value) {
            $properties[$key] = $value;
        }
        foreach ($properties["tabs"] as $key => $row) {
            if ($row["content"] instanceof Url) {
                $properties["tabs"][$key]['url'] = $row['content'];
            }
        }
        return [
            'properties' => $properties
        ];
    }
}