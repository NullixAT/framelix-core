<?php

namespace Framelix\Framelix\View\Backend;

use Framelix\Framelix\Backend\Sidebar;
use Framelix\Framelix\Config;
use Framelix\Framelix\Html\Compiler;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View\LayoutView;

use function call_user_func_array;
use function get_class;

use const FRAMELIX_MODULE;

/**
 * The base for all backend views
 */
abstract class View extends LayoutView
{
    /**
     * The default backend layout
     */
    public const LAYOUT_DEFAULT = 1;

    /**
     * Display the page in a small centered container with a slightly blurry background image
     * For login page and such stuff
     */
    public const LAYOUT_SMALL_CENTERED = 2;

    /**
     * Totally blank page with pure content container
     */
    public const LAYOUT_BLANK = 3;

    /**
     * Show sidebar on page
     * @var bool
     */
    protected bool $showSidebar = true;

    /**
     * The layout to use
     * @var int
     */
    protected int $layout = self::LAYOUT_DEFAULT;

    /**
     * Show the content including the layout
     */
    public function showContentWithLayout(): void
    {
        Compiler::compile("Framelix");
        $this->includeCompiledFilesForModule("Framelix");
        $this->includeCompiledFile("Framelix", "scss", "backend");
        $this->includeCompiledFile("Framelix", "scss", "backend-fonts");
        $this->includeCompiledFile("Framelix", "js", "backend");
        Compiler::compile(FRAMELIX_MODULE);
        $this->includeCompiledFilesForModule(FRAMELIX_MODULE);
        $sidebarClass = $this->showSidebar ? "Framelix\\" . FRAMELIX_MODULE . "\\Backend\\Sidebar" : null;
        $sidebarContent = null;
        if ($sidebarClass) {
            /** @var Sidebar $sidebarView */
            /** @phpstan-ignore-next-line */
            $sidebarView = new $sidebarClass();
            Buffer::start();
            $sidebarView->showDefaultSidebarStart();
            $sidebarView->showContent();
            $sidebarView->showDefaultSidebarEnd();
            $sidebarContent = Buffer::getAll();
        }
        Buffer::start();
        if ($this->contentCallable) {
            call_user_func_array($this->contentCallable, []);
        } else {
            $this->showContent();
        }
        $pageContent = Buffer::getAll();
        $htmlAttributes = new HtmlAttributes();
        $htmlAttributes->set('data-view', get_class(self::$activeView));
        $htmlAttributes->set('data-navigation', $sidebarClass);
        $htmlAttributes->set('data-layout', $this->layout);

        $iconUrl = Url::getUrlToFile((string)Config::get('backendIcon'));
        $logoUrl = Url::getUrlToFile((string)Config::get('backendLogo'));
        if ($iconUrl) {
            $this->addHeadHtml('<link rel="icon" href="' . $iconUrl . '">');
        } elseif ($logoUrl) {
            $this->addHeadHtml('<link rel="icon" href="' . $logoUrl . '">');
        }

        Buffer::start();
        echo '<!DOCTYPE html>';
        echo '<html lang="' . Lang::$lang . '" ' . $htmlAttributes . '>';
        $this->showDefaultPageStartHtml();
        echo '<body>';
        echo '<div class="framelix-page">';
        if ($sidebarClass) {
            ?>
            <nav class="framelix-sidebar">
                <div class="framelix-sidebar-inner">
                    <?= $sidebarContent ?>
                </div>
            </nav>
            <button class="framelix-button framelix-sidebar-toggle framelix-button-trans"
                    data-icon-left="menu"></button>
            <?php
        }
        ?>
        <div class="framelix-content">
            <div class="framelix-content-inner">
                <h1 class="framelix-page-title"><?= $this->getPageTitle(false) ?></h1>
                <?= $pageContent ?>
            </div>
        </div>
        <script>
          Framelix.initLate()
        </script>
        <?php
        echo '</div>';
        echo '</body></html>';
        echo Buffer::getAll();
    }

    /**
     * On access denied, redirect to login page if not already logged in
     */
    public function showAccessDenied(): void
    {
        if (!User::get() && !(View::$activeView instanceof Login)) {
            \Framelix\Framelix\View::getUrl(Login::class)->setParameter('redirect', (string)Url::create())->redirect();
        }
        parent::showAccessDenied();
    }
}