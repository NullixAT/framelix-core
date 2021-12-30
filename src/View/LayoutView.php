<?php

namespace Framelix\Framelix\View;

use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorHandler;
use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View;

use function call_user_func_array;
use function file_exists;
use function get_class;
use function htmlentities;

use const FRAMELIX_MODULE;

/**
 * A view that output content in a layout
 */
abstract class LayoutView extends View
{

    /**
     * Html to directly output in the <head> section of the page
     * @var string
     */
    protected string $headHtml = '';

    /**
     * If set, then use call this function instead of showContent()
     * @var callable|null
     */
    protected $contentCallable = null;

    /**
     * Add html to <head>
     * @param string $html
     * @return void
     */
    public function addHeadHtml(string $html): void
    {
        $this->headHtml .= "\n" . $html;
    }

    /**
     * Include all compiled for given module that are not marked as noInclude
     * @param string $module
     */
    public function includeCompiledFilesForModule(string $module): void
    {
        $metadata = Compiler::getDistMetadata($module);
        if ($metadata) {
            foreach ($metadata as $type => $groups) {
                foreach ($groups as $groupId => $row) {
                    if (!($row['options']['noInclude'] ?? null)) {
                        $this->includeCompiledFile($module, $type, $groupId);
                    }
                }
            }
        }
    }

    /**
     * Add a compiled file into the pages headHtml
     * @param string $module
     * @param string $type js|scss
     * @param string $id
     */
    public function includeCompiledFile(
        string $module,
        string $type,
        string $id
    ): void {
        $this->addHeadHtml(HtmlUtils::getIncludeTagForUrl(Compiler::getDistUrl($module, $type, $id)));
    }

    /**
     * Show the default <head> html tag
     */
    public function showDefaultPageStartHtml(): void
    {
        $distUrls = [];
        foreach (Config::$loadedModules as $module) {
            $metadata = Compiler::getDistMetadata($module);
            if ($metadata) {
                foreach ($metadata as $type => $groups) {
                    foreach ($groups as $groupId => $row) {
                        if (!file_exists(Compiler::getDistFilePath($module, $type, $groupId))) {
                            continue;
                        }
                        $distUrls[$module][$type][$groupId] = Compiler::getDistUrl($module, $type, $groupId);
                    }
                }
            }
        }
        ?>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <title><?= $this->getPageTitle(true) ?></title>
            <script>
              class FramelixInit {
                /** @type {function[]} */
                static early = []
                /** @type {function[]} */
                static late = []
              }

              (function () {
                // check for minimal supported browsers, of unsupported than stop any further execution
                // which effectively excludes IE and legacy edge < 18
                if (!DataTransfer || !DataTransfer.prototype.setDragImage) {
                  setTimeout(function () {
                    document.body.innerHTML = '<div style="padding:20px; font-family: Arial, sans-serif; font-size: 24px"><?=Lang::get(
                        '__framelix_browser_unsupported__'
                    )?></div>'
                  }, 200)
                }
              })()
            </script>
            <?= HtmlUtils::getIncludeTagForUrl(Compiler::getDistUrl("Framelix", "js", "general-early")); ?>
            <script>
              FramelixDeviceDetection.init()
            </script>
            <?= $this->headHtml ?>
            <script>
              FramelixConfig.applicationUrl = <?=JsonUtils::encode(Url::getApplicationUrl())?>;
              FramelixConfig.modulePublicUrl = <?=JsonUtils::encode(Url::getModulePublicFolderUrl(FRAMELIX_MODULE))?>;
              FramelixConfig.compiledFileUrls = <?=JsonUtils::encode($distUrls)?>;
              FramelixLang.lang = <?=JsonUtils::encode(Lang::$lang)?>;
              FramelixLang.langFallback = <?=JsonUtils::encode(Config::get('languageFallback') ?? 'en')?>;
              FramelixLang.supportedLanguages = <?=JsonUtils::encode(Lang::getSupportedLanguages())?>;
              FramelixLang.values = <?=JsonUtils::encode(Lang::getValuesForSupportedLanguages())?>;
              FramelixToast.queue = <?=JsonUtils::encode(Toast::getQueueMessages(true))?>;
              Framelix.initEarly()
            </script>
        </head>
        <?php
    }

    /**
     * Get translated page title
     * @param bool $escape Does remove html tags and html escape the string
     * @return string
     */
    public function getPageTitle(bool $escape): string
    {
        return View::getTranslatedPageTitle(get_class($this), $escape, $this->pageTitle);
    }

    /**
     * Show a container where the view gets loaded into that container at the moment it becomes first visible
     */
    public function showAsyncContainer(): void
    {
        $jsonData = JsonUtils::encode($this);
        $randomId = RandomGenerator::getRandomHtmlId();
        ?>
        <div id="<?= $randomId ?>"></div>
        <script>
          (function () {
            const view = FramelixView.createFromPhpData(<?=$jsonData?>)
            view.render()
            $("#<?=$randomId?>").replaceWith(view.container)
          })()
        </script>
        <?php
    }

    /**
     * Show a soft error without throwing an exception and without logging an error and stop script execution after that
     * @param string $message
     * @return never
     */
    public function showSoftError(string $message): never
    {
        Buffer::clear();
        $this->contentCallable = function () use ($message) {
            ?>
            <div class="framelix-alert framelix-alert-error">
                <?= htmlentities(Lang::get($message)) ?>
            </div>
            <?php
        };
        $this->showContentBasedOnRequestType();
        Framelix::stop();
    }

    /**
     * Show error in case of an exception
     * @param array $logData
     * @return never
     */
    public function onException(array $logData): never
    {
        Buffer::clear();
        $this->contentCallable = function () use ($logData) {
            ErrorHandler::showErrorFromExceptionLog($logData);
        };
        $this->showContentBasedOnRequestType();
        Framelix::stop();
    }

    /**
     * Show only content data when async, show with layout when not async
     */
    public function showContentBasedOnRequestType(): void
    {
        if (Request::isAsync()) {
            if ($this->contentCallable) {
                call_user_func_array($this->contentCallable, []);
            } else {
                $this->showContent();
            }
            return;
        }
        $this->showContentWithLayout();
    }

    /**
     * Show content
     */
    abstract public function showContent(): void;

    /**
     * Show content with layout
     */
    abstract public function showContentWithLayout(): void;
}