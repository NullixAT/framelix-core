<?php

namespace Framelix\Framelix\Backend;

use Framelix\Framelix\AppUpdate;
use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Toggle;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\View;

use function class_exists;
use function file_exists;
use function get_class;
use function in_array;
use function is_string;
use function str_starts_with;

use const SORT_ASC;

/**
 * Backend sidebar base from which every other backend sidebar must extend
 */
abstract class Sidebar
{
    /**
     * Internal link data
     * @var array
     */
    public array $linkData = [];

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'settings':
                if (Config::get('languageMultiple') && $supportedLanguages = Lang::getEnabledLanguages()) {
                    $field = new Select();
                    $field->name = "languageSelect";
                    $field->label = "__framelix_configuration_module_language_pagetitle__";
                    foreach ($supportedLanguages as $supportedLanguage) {
                        $url = Url::getBrowserUrl();
                        $url->replaceLanguage($supportedLanguage);
                        $field->addOption(
                            $url->getUrlAsString(),
                            Lang::ISO_LANG_CODES[$supportedLanguage] ?? $supportedLanguage
                        );
                    }
                    $field->defaultValue = Url::getBrowserUrl()->getUrlAsString();
                    $field->show();
                }

                $field = new Toggle();
                $field->name = "darkMode";
                $field->label = Lang::get('__framelix_darkmode__') . ' <span class="material-icons">dark_mode</span>';
                $field->show();
                ?>
                <script>
                  (async function () {
                    const languageSelect = FramelixFormField.getFieldByName(FramelixModal.modalsContainer, 'languageSelect')
                    if (languageSelect) await languageSelect.rendered
                    if (languageSelect) {
                      languageSelect.container.on(FramelixFormField.EVENT_CHANGE, function () {
                        window.location.href = languageSelect.getValue()
                      })
                    }
                    const darkModeToggle = FramelixFormField.getFieldByName(FramelixModal.modalsContainer, 'darkMode')
                    if (darkModeToggle) await darkModeToggle.rendered
                    if (darkModeToggle) {
                      darkModeToggle.container.on(FramelixFormField.EVENT_CHANGE, function () {
                        FramelixLocalStorage.set('framelix-darkmode', darkModeToggle.getValue() === '1')
                        FramelixDeviceDetection.updateAttributes()
                      })
                      darkModeToggle.setValue(FramelixLocalStorage.get('framelix-darkmode'))
                    }
                  })()
                </script>
                <?php
                break;
        }
    }

    /**
     * Start a group (collapsable)
     * @param string $label
     * @param string $icon The icon
     * @param string|null $badgeText Optional red badge text
     */
    public function startGroup(string $label, string $icon = "menu", ?string $badgeText = null): void
    {
        $this->linkData = [
            "type" => "group",
            "label" => $label,
            "links" => [],
            "icon" => $icon,
            "badgeText" => $badgeText
        ];
    }

    /**
     * Add a link
     * @param string|Url $url Could be a view class name or a direct URL
     * @param string|null $label The label, if null then use the page title if a view is given
     * @param string $icon The icon
     * @param string $target The link target
     * @param array|null $urlParameters Additional url parameters to add to
     * @param array|null $viewUrlParameters Additional view url parameters. Only required when view has a custom url with regex placeholders
     * @param string|null $badgeText Optional red badge text
     */
    public function addLink(
        string|Url $url,
        ?string $label = null,
        string $icon = "adjust",
        string $target = "_self",
        ?array $urlParameters = null,
        ?array $viewUrlParameters = null,
        ?string $badgeText = null
    ): void {
        if (!$this->linkData) {
            $this->linkData = [
                "type" => "single",
                "links" => [],
            ];
        }
        $this->linkData["links"][] = [
            "url" => $url,
            "urlParameters" => $urlParameters,
            "viewUrlParameters" => $viewUrlParameters,
            "label" => $label,
            "target" => $target,
            "icon" => $icon,
            "badgeText" => $badgeText
        ];
    }

    /**
     * Show global context select
     * @param string $contextKey The context key, must also be defined in config -> urlGlobalContextParameterKeys
     * @param array $options The options to be selectable, key is parameter value, value is label
     * @param mixed $selectedValue The selected value
     */
    public function showGlobalContextSelects(string $contextKey, array $options, mixed $selectedValue): void
    {
        echo '<div class="framelix-sidebar-context-select">';
        $keepKeys = Config::get('urlGlobalContextParameterKeys', 'array');
        if (!in_array($contextKey, $keepKeys)) {
            throw new Exception(
                "Missing key '$contextKey' in config->urlGlobalContextParameterKeys",
                ErrorCode::BACKEND_GLOBALCONTEXT_MISSINGKEY
            );
        }
        $field = new Select();
        $field->name = "contextSelect[$contextKey]";
        $field->addOptions($options);
        $field->defaultValue = $selectedValue ?? Request::getGet($contextKey);
        $field->minWidth = "100%";
        $field->show();
        echo '</div>';
    }

    /**
     * Show default sidebar start
     */
    public function showDefaultSidebarStart(): void
    {
        if (!Config::get('backendDefaultView') || !class_exists(Config::get('backendDefaultView'))) {
            echo '<div class="framelix-alert framelix-alert-error">Missing correct class in config key "backendDefaultView" in config-module.php</div>';
        } else {
            $logoUrl = Url::getUrlToFile((string)Config::get('backendLogo'));
            if ($logoUrl) {
                ?>
                <div class="framelix-sidebar-logo">
                    <a href="<?= View::getUrl(Config::get('backendDefaultView')) ?>"><img
                                src="<?= $logoUrl ?>" alt="App Logo" title="__framelix_backend_startpage__"></a>
                </div>
                <?php
                echo '<div class="framelix-sidebar-entries">';
            }
        }
        if (UserToken::getByCookie()->simulatedUser ?? null) {
            ?>
            <div class="framelix-alert framelix-alert-warning">
                <div>
                    <?= Lang::get('__framelix_simulateuser_info__', [UserToken::getByCookie()->simulatedUser->email]) ?>
                    <a href="<?= View::getUrl(View\Backend\User\CancelSimulation::class)->setParameter(
                        'redirect',
                        Url::getBrowserUrl()
                    ) ?>"><?= Lang::get('__framelix_simulateuser_cancel__') ?></a>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Show default sidebar end
     */
    public function showDefaultSidebarEnd(): void
    {
        $this->startGroup("__framelix_edituser_sidebar_title__", "people");
        $this->addLink(View\Backend\User\Index::class, null, "add");
        $this->addLink(View\Backend\User\Search::class, null, "manage_search");
        $this->showHtmlForLinkData();

        // get system values
        $this->startGroup("__framelix_systemvalues__", "dns");
        $viewFiles = FileUtils::getFiles(
            FileUtils::getModuleRootPath(FRAMELIX_MODULE) . "/src/View/Backend/SystemValue",
            "~\.php$~",
            true
        );
        foreach ($viewFiles as $viewFile) {
            $viewClass = ClassUtils::getClassNameForFile($viewFile);
            $meta = View::getMetadataForView($viewClass);
            if ($meta) {
                $this->addLink($viewClass, null, "radio_button_unchecked");
            }
        }
        $this->showHtmlForLinkData(true);

        $this->addLink(View\Backend\Config\Index::class, null, "settings");
        $this->showHtmlForLinkData();

        $this->startGroup("__framelix_view_backend_logs__", "storage");
        $this->addLink(View\Backend\Logs\ErrorLogs::class);
        $this->addLink(View\Backend\Logs\SystemEventLogs::class);
        $this->showHtmlForLinkData();

        $updateAppUpdateFile = AppUpdate::UPDATE_CACHE_FILE;
        $badgeText = null;
        if (file_exists($updateAppUpdateFile)) {
            $badgeText = '1';
        }
        $this->addLink(View\Backend\AppUpdate::class, icon: 'system_update', badgeText: $badgeText);
        $this->showHtmlForLinkData();

        $this->startGroup("__framelix_developer_options__", "developer_mode");
        $this->addLink(View\Backend\Dev\Update::class, null, "system_update");
        $this->addLink(View\Backend\Dev\LangEditor::class, null, "g_translate");
        $this->showHtmlForLinkData();

        if (User::get()) {
            $this->addLink(
                View\Backend\UserProfile\Index::class,
                '<div>' . Lang::get(
                    '__framelix_view_backend_userprofile_index__'
                ) . '</div><div class="framelix-sidebar-label-nowrap framelix-sidebar-label-small">' . User::get(
                )->email . '</div>',
                "person"
            );
            $this->addLink(View\Backend\Logout::class, "__framelix_logout__", "logout");
            $this->showHtmlForLinkData();
        }

        $url = JsCall::getCallUrl(__CLASS__, 'settings');
        echo '<div class="framelix-sidebar-entry framelix-sidebar-settings" data-url="' . $url . '">';
        echo '<button class="framelix-button framelix-button-trans framelix-button-block" data-icon-left="settings">'
            . Lang::get('__framelix_sidebar_settings__')
            . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Show html for given link data
     * @param bool $sortByLabel If it is a collapsable then sort entries by label
     */
    public function showHtmlForLinkData(bool $sortByLabel = false): void
    {
        $linkData = $this->linkData;
        $this->linkData = [];
        $type = $linkData['type'];
        // check if a link is currently the active URL/view in browser
        $activeKey = null;
        $currentUrl = Url::create();
        $currentUrlStr = $currentUrl->getUrlAsString();
        foreach ($linkData['links'] as $key => $row) {
            /** @var Url|string $url */
            $url = $row['url'];
            if (is_string($url)) {
                $viewUrl = View::getUrl($url);
                if (!$viewUrl) {
                    unset($linkData['links'][$key]);
                    continue;
                }
                $row['url'] = $viewUrl;
                $meta = View::getMetadataForView($url);
                if (!User::hasRole(View::replaceAccessRoleParameters($meta['accessRole'], $viewUrl))) {
                    unset($linkData['links'][$key]);
                    continue;
                }
                if ($row['label'] === null) {
                    $linkData['links'][$key]['label'] = View::getTranslatedPageTitle($url, true);
                }
                if (get_class(View::$activeView) === $url) {
                    if (isset($row['urlParameters'])) {
                        $matched = true;
                        foreach ($row['urlParameters'] as $urlParamKey => $urlParamValue) {
                            if ((string)$urlParamValue !== $currentUrl->getParameter($urlParamKey)) {
                                $matched = false;
                                break;
                            }
                        }
                        if ($matched) {
                            $activeKey = $key;
                        }
                    } else {
                        $activeKey = $key;
                    }
                }
            } elseif (str_starts_with($currentUrlStr, $url->getUrlAsString())) {
                $activeKey = $key;
            }
            $linkData['links'][$key]['label'] = Lang::get($linkData['links'][$key]['label']);
        }
        if (!$linkData['links']) {
            return;
        }
        echo '<div class="framelix-sidebar-entry">';
        if ($type === 'group') {
            ?>
            <div class="framelix-sidebar-collapsable <?= $activeKey !== null ? 'framelix-sidebar-collapsable-active' : '' ?>">
            <button class="framelix-sidebar-collapsable-title framelix-activate-toggle-handler">
                <span class="framelix-sidebar-main-icon material-icons"><?= $linkData['icon'] ?></span> <span
                        class="framelix-sidebar-label"><?= $linkData['badgeText'] !== null ? '<span class="framelix-sidebar-badge">' . $linkData['badgeText'] . '</span>' : '' ?><?= Lang::get(
                        $linkData['label']
                    ) ?></span>
            </button>
            <div class="framelix-sidebar-collapsable-container">
            <?php
        }
        if ($sortByLabel) {
            ArrayUtils::sort($linkData['links'], "label", [SORT_ASC]);
        }
        foreach ($linkData['links'] as $key => $row) {
            $url = $row['url'];
            if (is_string($url)) {
                $url = View::getUrl($url, $row['viewUrlParameters'] ?? null);
            }
            if ($row['urlParameters']) {
                $url = clone $url;
                $url->addParameters($row['urlParameters']);
            }
            $url = $url->getUrlAsString();
            ?>
            <a href="<?= $url ?>"
               class="framelix-sidebar-link <?= $activeKey === $key ? 'framelix-sidebar-link-active' : '' ?>">
                <span class="<?= $type !== 'group' ? 'framelix-sidebar-main-icon' : '' ?> material-icons"><?= $row['icon'] ?></span>
                <div class="framelix-sidebar-label"><?= $row['badgeText'] !== null ? '<span class="framelix-sidebar-badge">' . $row['badgeText'] . '</span>' : '' ?><?= $row['label'] ?></div>
            </a>
            <?php
        }
        if ($type === 'group') {
            ?>
            </div></div>
            <?php
        }
        echo '</div>';
    }

    /**
     * Show the navigation content
     */
    abstract public function showContent(): void;
}