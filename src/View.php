<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\ClassUtils;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\StringUtils;
use JsonSerializable;
use ReflectionClass;

use function array_key_last;
use function class_parents;
use function count;
use function crc32;
use function explode;
use function file_exists;
use function filemtime;
use function get_class;
use function http_response_code;
use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function preg_replace;
use function reset;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

use const FRAMELIX_MODULE;
use const SORT_ASC;

/**
 * The base for all views
 */
abstract class View implements JsonSerializable
{
    /**
     * Increase this number when something important changes in metadata handling
     */
    public const METADATA_VERSION = 1;

    /**
     * The current active view
     * @var View|null
     */
    public static ?View $activeView = null;

    /**
     * All currently available views
     * The index is the class name, the value is view metadata
     * @var array
     */
    public static array $availableViews = [];

    /**
     * Define a custom url for this view
     * Can contain a regex with ~regex~
     * @var string|null
     */
    protected ?string $customUrl = null;

    /**
     * If url does match a custom url regex, then this will contain the regex match result
     * @var array|null
     */
    protected ?array $customUrlParameters = null;

    /**
     * This view requires the given access role to be viewable by the user
     * true = Any logged-in user
     * false = No logged-in user
     * string = Access role, multiple separated by ,
     * * = Anything, no checks at all
     * Placeholder {module} is replaced with current module
     * Every other placeholder {xxx} is taken and replaced with the corresponding url parameters
     * @var string|bool
     */
    protected string|bool $accessRole = '';

    /**
     * The page title
     * {classLangKey} will be replaced with the view classes lang key
     * @var string
     */
    protected string $pageTitle = '{classLangKey}';

    /**
     * Allow this view in dev mode only
     * @var bool
     */
    protected bool $devModeOnly = false;

    /**
     * If this view is loaded in context of a tab, then this contains the tab id
     * @var string|null
     */
    protected ?string $tabId = null;

    /**
     * Allow multilanguage for this page
     * If disabled, the page has the default system language (or the language you set in Lang::$lang)
     * @var bool
     */
    protected bool $multilanguage = true;

    /**
     * Get translated page title
     * @param string $viewClass
     * @param bool $escape Does remove html tags and html escape the string
     * @param string|null $override Override page title, used when come from a view instance
     * @return string
     */
    public static function getTranslatedPageTitle(string $viewClass, bool $escape, ?string $override = null): string
    {
        if (__CLASS__ !== static::class) {
            throw new Exception(
                "getTranslatedPageTitle only can be called on " . __CLASS__ . ", not on a child. This prevent unintentional class loads",
                ErrorCode::TOPLEVEL_CALL_ONLY
            );
        }

        // go over cached metadata because this does save a lot of memory because classes need not to be parsed and loaded into memory
        // imagine loading 50 classes into memory just because you want the default page title, waste of time and money
        $meta = self::getMetadataForView($viewClass);
        $pageTitle = $override ?? $meta['pageTitle'] ?? null;
        $pageTitle = str_replace('{classLangKey}', ClassUtils::getLangKey($viewClass), $pageTitle);
        if ($escape) {
            return HtmlUtils::escape(strip_tags(Lang::get($pageTitle)));
        } else {
            return Lang::get($pageTitle);
        }
    }

    /**
     * Get url for given view class
     * @param string $viewClass
     * @param array|null $parameters If url is expected to be a regex, then replace regex parameters with it
     * @return Url|null Null if class is not found or not mapped
     */
    public static function getUrl(string $viewClass, ?array $parameters = null): ?Url
    {
        if (__CLASS__ !== static::class) {
            throw new Exception(
                "getUrl only can be called on " . __CLASS__ . ", not on a child. This prevent unintentional class loads",
                ErrorCode::TOPLEVEL_CALL_ONLY
            );
        }
        if (!isset(self::$availableViews[$viewClass])) {
            return null;
        }
        $metadata = self::getMetadataForView($viewClass);
        $urlPath = $metadata['customUrl'] ?? $metadata['url'] ?? null;
        if (!$urlPath) {
            throw new Exception("No url mapped to view $viewClass", ErrorCode::VIEW_NOURL);
        }
        // replace regex parameters
        if (str_starts_with($urlPath, "~")) {
            $urlPath = trim($urlPath, "~");
            if (is_array($parameters)) {
                foreach ($parameters as $key => $value) {
                    $urlPath = preg_replace("~\(\?<$key>.*?\)~", $value, $urlPath);
                }
            }
        }
        $url = Url::getApplicationUrl();
        // check if multilanguage, append language in uri
        if (Config::get('languageMultiple') && $metadata['multilanguage']) {
            $url->appendPath("/" . Lang::$lang . "/");
        }
        $url->appendPath($urlPath);
        return $url;
    }

    /**
     * Find matching view for given url
     * @param Url $url
     * @return View|null
     */
    public static function findViewForUrl(Url $url): ?View
    {
        $applicationUrl = Url::getApplicationUrl();
        $relativeUrl = rtrim($url->getRelativePath($applicationUrl), "/");
        if (Config::get('languageMultiple')) {
            $supportedLanguages = Config::get('languagesSupported');
            if ($supportedLanguages) {
                $foundLanguage = $url->getLanguage();
                if ($foundLanguage) {
                    $relativeUrl = substr($relativeUrl, strlen("/$foundLanguage"));
                }
            }
        }
        $modules = [];
        foreach (self::$availableViews as $viewClass => $viewFile) {
            $module = ClassUtils::getModuleForClass($viewClass);
            $modules[$module] = $module;
        }
        $metadata = [];
        foreach ($modules as $module) {
            $metadata = ArrayUtils::merge($metadata, self::getMetadata($module));
        }
        if (!isset($metadata['views'])) {
            return null;
        }
        $matchedViews = [];
        foreach (self::$availableViews as $class => $row) {
            if (isset($row['url'])) {
                if (isset($row['customUrl'])) {
                    if (str_starts_with($row['customUrl'], "~")) {
                        if (preg_match($row['customUrl'], $relativeUrl, $match)) {
                            $matchedViews[] = [
                                "class" => $class,
                                "depth" => $row['depth'],
                                'parameters' => $match
                            ];
                        }
                    } elseif ($row['customUrl'] === $relativeUrl) {
                        $matchedViews[] = ["class" => $class, "depth" => $row['depth']];
                    }
                } elseif ($row['url'] === $relativeUrl) {
                    $matchedViews[] = ["class" => $class, "depth" => $row['depth']];
                }
            }
        }
        if ($matchedViews) {
            ArrayUtils::sort($matchedViews, "depth", [SORT_ASC]);
            $matchedView = reset($matchedViews);
            $viewClass = $matchedView['class'];
            /** @var View $view */
            $view = new $viewClass();
            $view->customUrlParameters = $matchedView['parameters'] ?? null;
            return $view;
        }
        return null;
    }

    /**
     * Load view for current url
     * @param bool $redirectIfLanguageIsInvalid If app is multilanguage and language is missing/invalid in url, redirect to same page with correct lang included
     * @param bool $setActiveLanguageFromUrl Does set the current active language to the detected language from url
     */
    public static function loadViewForCurrentUrl(
        bool $redirectIfLanguageIsInvalid = true,
        bool $setActiveLanguageFromUrl = true
    ): void {
        $url = Url::create();
        $view = self::findViewForUrl($url);
        if (!$view) {
            http_response_code(404);
            return;
        }
        $applicationUrl = Url::getApplicationUrl();
        $relativeUrl = rtrim($url->getRelativePath($applicationUrl), "/");
        $foundLanguage = null;
        if (Config::get('languageMultiple')) {
            $supportedLanguages = Config::get('languagesSupported');
            if ($supportedLanguages) {
                $foundLanguage = $url->getLanguage();
            }
        }
        $viewMetadata = self::getMetadataForView($view);
        if (Config::get('languageMultiple') && $viewMetadata['multilanguage']) {
            if ($redirectIfLanguageIsInvalid && !$foundLanguage) {
                $applicationUrl->appendPath("/" . Lang::$lang . "/");
                $applicationUrl->appendPath($relativeUrl);
                $applicationUrl->redirect();
            }
            if ($setActiveLanguageFromUrl && $foundLanguage) {
                Lang::$lang = $foundLanguage;
            }
        }
        self::$activeView = $view;
        if ($tabId = Request::getHeader('HTTP_X_TAB_ID')) {
            self::$activeView->tabId = $tabId;
        }
        $accessRole = self::replaceAccessRoleParameters($view->accessRole, $url);
        if (!User::hasRole($accessRole)) {
            self::$activeView->showAccessDenied();
        } else {
            Buffer::start();
            $view->onRequest();
            if (Request::isAsync()) {
                JsonUtils::output(['content' => Buffer::getAll()]);
            } else {
                echo Buffer::getAll();
            }
        }
    }

    /**
     * Add all views for given module
     * @param string $module
     */
    public static function addAvailableViewsByModule(string $module): void
    {
        $metadata = self::getMetadata($module);
        if (isset($metadata['views'])) {
            foreach ($metadata['views'] as $className => $row) {
                self::addAvailableView($className);
            }
        }
    }

    /**
     * Add given view as available view
     * @param string $viewClass
     */
    public static function addAvailableView(string $viewClass): void
    {
        $metadata = self::getMetadataForView($viewClass);
        if ($metadata) {
            if ($metadata['devModeOnly'] && !Config::isDevMode()) {
                return;
            }
            self::$availableViews[$viewClass] = $metadata;
        }
    }

    /**
     * Get metadata for given view
     * @param string|View $view
     * @return array|null
     */
    public static function getMetadataForView(string|View $view): ?array
    {
        $module = ClassUtils::getModuleForClass($view);
        return self::getMetadata($module)['views'][is_string($view) ? $view : get_class($view)] ?? null;
    }

    /**
     *Replace access role parameters
     * @param mixed $accessRole
     * @param Url|null $url Url to replace parameters from
     * @return mixed
     */
    public static function replaceAccessRoleParameters(mixed $accessRole, ?Url $url = null): mixed
    {
        if (is_string($accessRole)) {
            $accessRole = str_replace(['{module}'], [FRAMELIX_MODULE], $accessRole);
            $parameters = $url->getParameters();
            if (is_array($parameters)) {
                foreach ($parameters as $key => $parameter) {
                    if (is_string($parameter)) {
                        $accessRole = str_replace('{' . $key . '}', StringUtils::slugify($parameter), $accessRole);
                    }
                }
            }
        }
        return $accessRole;
    }

    /**
     * Get metadata for given module
     * @param string $module
     * @return array|null
     */
    public static function getMetadata(string $module): ?array
    {
        $moduleFolder = FileUtils::getModuleRootPath($module);
        $metadataFile = "$moduleFolder/_meta/views.json";
        $metadataFileExist = file_exists($metadataFile);
        return $metadataFileExist ? JsonUtils::readFromFile($metadataFile) : null;
    }

    /**
     * Update metadata for given module
     * @param string $module
     */
    public static function updateMetadata(string $module): void
    {
        if (!Config::isDevMode()) {
            return;
        }
        $moduleFolder = FileUtils::getModuleRootPath($module);
        $moduleFolderLength = strlen($moduleFolder);
        $metadataFile = "$moduleFolder/_meta/views.json";
        $metadataFileExist = file_exists($metadataFile);
        $metadata = $metadataFileExist ? JsonUtils::readFromFile($metadataFile) : null;
        $metadataTime = $metadataFileExist ? filemtime($metadataFile) : 0;
        // in dev mode, we should check if we need to update metadata files based timestamps
        $update = !$metadataFileExist;
        $viewFiles = FileUtils::getFiles("$moduleFolder/src/View", "~\.php$~", true);
        $viewFilesRelative = [];
        foreach ($viewFiles as $viewFile) {
            $viewFilesRelative[] = substr($viewFile, $moduleFolderLength);
        }
        $directoryListHash = self::METADATA_VERSION . "|" . crc32(implode("|", $viewFilesRelative));
        if (($metadata['directoryListHash'] ?? null) !== $directoryListHash) {
            // something has changed in directory files during last metadata update so now force update metadata
            $update = true;
        } else {
            // check filetimes if we really need an update
            foreach ($viewFiles as $viewFile) {
                if (filemtime($viewFile) > $metadataTime) {
                    $update = true;
                    break;
                }
            }
        }
        if ($update) {
            $metadata = [
                'directoryListHash' => $directoryListHash
            ];
            foreach ($viewFiles as $viewFile) {
                $viewClass = ClassUtils::getClassNameForFile($viewFile);
                $reflection = new ReflectionClass($viewClass);
                if ($reflection->isAbstract()) {
                    continue;
                }
                $exp = explode("\\", $viewClass);
                unset($exp[0], $exp[1], $exp[2]);

                $lastKey = array_key_last($exp);
                $lastPart = $exp[$lastKey];

                if ($lastPart === 'Index') {
                    unset($exp[$lastKey]);
                }

                $defaultProps = $reflection->getDefaultProperties();
                if (($defaultProps['accessRole'] ?? null) === null) {
                    throw new Exception(
                        "Property $viewClass->'accessRole' must be set, use \"*\" to allow everybody",
                        ErrorCode::VIEW_ACCESSROLE_MISSING
                    );
                }
                $pageTitle = $defaultProps['pageTitle'] ?? '';
                $url = "/" . strtolower(implode("/", $exp));
                $metadata['views'][$viewClass] = [
                    'accessRole' => $defaultProps['accessRole'],
                    'customUrl' => $defaultProps['customUrl'],
                    'devModeOnly' => $defaultProps['devModeOnly'],
                    'multilanguage' => $defaultProps['multilanguage'],
                    'depth' => count(class_parents($viewClass)) + (str_starts_with(
                            $defaultProps['customUrl'] ?? '',
                            "~"
                        ) ? 100 : 0),
                    'pageTitle' => $pageTitle,
                    'url' => rtrim($url, "/"),
                ];
            }
            JsonUtils::writeToFile($metadataFile, $metadata, true);
        }
    }

    /**
     * Get url to the current class
     * @param array|null $parameters If url is expected to be a regex, then replace regex parameters with it
     * @return Url
     */
    public function getSelfUrl(?array $parameters = null): Url
    {
        return View::getUrl($this::class, $parameters);
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $class = get_class($this);
        $properties = [
            'url' => View::getUrl($class),
            'class' => $class
        ];
        return [
            'properties' => $properties
        ];
    }

    /**
     * Show access denied message and stop script execution after that
     */
    public function showAccessDenied(): void
    {
        http_response_code(403);
        $this->showSoftError('__framelix_error_access_denied__');
    }

    /**
     * Show an error that indicate invalid url
     * For example when required parameters are missing
     */
    public function showInvalidUrlError(): void
    {
        $this->showSoftError('__framelix_error_invalid_url__');
    }

    /**
     * Show a soft error without throwing an exception and without logging an error and stop script execution after that
     * @param string $message
     * @return never
     */
    public function showSoftError(string $message): never
    {
        Buffer::clear();
        echo Lang::get($message);
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
        ErrorHandler::showErrorFromExceptionLog($logData);
        Framelix::stop();
    }

    /**
     * On request
     */
    abstract public function onRequest(): void;
}