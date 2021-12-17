<?php

namespace Framelix\Framelix\View\Backend\Tests;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Html\Table;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View\Backend\View;

use function ini_set;
use function str_repeat;

class LayoutDemo extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "*";

    /**
     * Only in dev mode
     * @var bool
     */
    protected bool $devModeOnly = true;

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->showContentWithLayout();
    }

    /**
     * Show the page content without layout
     */
    public function showContent(): void
    {
        ini_set("memory_limit", "512m");
        $table = new Table();
        $table->createHeader([
            'ico1' => "Ico1",
            'ico2' => "Ico2",
            'ico3' => "",
            'foo' => "bar",
            'date' => "Date",
            'str' => "Str",
            'bool' => "Bool"
        ]);
        for ($i = 0; $i <= 30; $i++) {
            $ico = new TableCell();
            $ico->icon = "check";
            $ico->iconColor = "error";
            $ico->iconTooltip = "foobar";

            $ico2 = new TableCell();
            $ico2->icon = "clear";
            $ico2->iconColor = "#acfacc";
            $ico2->iconTooltip = "foobar";

            $ico3 = new TableCell();
            $ico3->icon = "add";
            $ico3->iconColor = "#4ca10a";
            $ico3->iconUrl = "#foop";

            $rowKey = $table->createRow([
                'ico1' => $ico,
                'ico2' => $ico2,
                'ico3' => $ico3,
                'foo' => RandomGenerator::getRandomString(3, 20),
                'date' => DateTime::create("-" . RandomGenerator::getRandomInt(0, 99999999) . " seconds"),
                'str' => RandomGenerator::getRandomString(3, 20),
                'bool' => (bool)RandomGenerator::getRandomInt(0, 1),
            ]);
            $table->getRowHtmlAttributes($rowKey)->addClass('foo');
            $table->getCellHtmlAttributes($rowKey, "foo")->addClass('foo');
        }
        $table->initialSort = ["+foo"];
        $table->show();

        $tableClone = clone $table;
        $tableClone->id = RandomGenerator::getRandomHtmlId();
        $tableClone->checkboxColumn = true;
        $tableClone->show();
        ?>

        <p title="Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 Foo Bar 123 ">
            Tooltip really large
        </p>
        <button class="framelix-button" onclick="FramelixPopup.showPopup(this, 'Small me')">Popup small</button>
        <button class="framelix-button" onclick="FramelixPopup.showPopup(this, '<?= str_repeat("larger me ", 300) ?>')">
            Popup large
        </button>
        <button class="framelix-button"
                onclick="FramelixPopup.showPopup(this, '<?= str_repeat("larger me<br/>", 300) ?>')">
            Popup too high
        </button>

        <button class="framelix-button" onclick="FramelixModal.show('<?= str_repeat("larger me ", 300) ?>')">
            Modal
        </button>
        <button class="framelix-button" onclick="FramelixModal.confirm('<?= str_repeat("larger me ", 300) ?>')">
            Modal confirm
        </button>
        <button class="framelix-button" onclick="FramelixModal.alert('<?= str_repeat("larger me ", 300) ?>')">
            Modal alert
        </button>
        <button class="framelix-button" onclick="FramelixModal.prompt('<?= str_repeat("larger me ", 300) ?>')">
            Modal prompt
        </button>
        <button class="framelix-button" onclick="FramelixModal.confirm('<?= str_repeat("larger me<br/>", 600) ?>')">
            Modal large confirm
        </button>
        <?php
        for ($i = 1; $i <= 6; $i++) {
            ?>
            <h<?= $i ?>>This is a h<?= $i ?> title</h<?= $i ?>>
            <?
        }
        ?>
        <p>
            This is a paragraph
        </p>
        <div class="framelix-loading"></div> Loading me<br/>
        <div class="framelix-loading"></div> Loading me<br/>
        <div class="framelix-loading"></div> Loading me<br/>
        <?
        $colorStyles = ['', 'primary', 'error', 'warning', 'success'];
        foreach ($colorStyles as $colorStyle) {
            ?>
            <div class="framelix-responsive-grid">
                <button class="framelix-button framelix-button-<?= $colorStyle ?>" data-icon-left="email">
                    framelix-button in a 2x grid
                </button>
                <button class="framelix-button framelix-button-<?= $colorStyle ?>">
                    This is a framelix-button in a 2x grid
                </button>
            </div>
            <div class="framelix-responsive-grid" data-grid-size="3">
                <button class="framelix-button framelix-button-<?= $colorStyle ?>" data-icon-left="email">
                    This is a framelix-button in a 3x grid
                </button>
                <button class="framelix-button framelix-button-<?= $colorStyle ?>">
                    This is a framelix-button in a 3x grid
                </button>
                <button class="framelix-button framelix-button-<?= $colorStyle ?>">
                    This is a framelix-button in a 3x grid
                </button>
            </div>
            <button class="framelix-button framelix-button-block framelix-button-<?= $colorStyle ?>">
                This is a framelix-button block
            </button>
            <button class="framelix-button framelix-button-block framelix-button-small framelix-button-<?= $colorStyle ?>">
                This is a framelix-button block small
            </button>
            <button class="framelix-button framelix-button-block framelix-button-small framelix-button-<?= $colorStyle ?>"
                    data-icon-left="email">
                This is a framelix-button block small
            </button>
            <button class="framelix-button framelix-button-block framelix-button-small framelix-button-trans"
                    data-icon-left="email">
                This is a framelix-button block trans
            </button>
            <div class="framelix-alert framelix-alert-<?= $colorStyle ?>">
                Alert Window
            </div>
            <button class="framelix-button"
                    onclick="FramelixPopup.showPopup(this, '<?= str_repeat(
                        "larger me ",
                        5
                    ) ?>', {color:'<?= $colorStyle ?>'})">
                Popup <?= $colorStyle ?>
            </button>
            <?
        }
    }
}