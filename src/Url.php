<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Storable\UserToken;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\CryptoUtils;
use Framelix\Framelix\Utils\FileUtils;
use JsonSerializable;

use function file_exists;
use function http_build_query;
use function http_response_code;
use function is_array;
use function parse_str;
use function parse_url;
use function realpath;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function time;
use function trim;

use const FRAMELIX_APP_ROOT;
use const FRAMELIX_ENTRY_POINT_FOLDER;
use const FRAMELIX_MODULE;

/**
 * URL utilities for frequent tasks
 */
class Url implements JsonSerializable
{
    /**
     * Contains all url data
     * @var array
     */
    public array $urlData = [];

    /**
     * Get a url that is pointing to a public file on disk
     * It does automatically append anti-cache parameter
     * @param string $path
     * @param string $module If path is relative, then search in the given module for the file
     * @param bool $antiCacheParameter
     * @return Url|null Null if file is not found
     */
    public static function getUrlToFile(
        string $path,
        string $module = FRAMELIX_MODULE,
        bool $antiCacheParameter = true
    ): ?self {
        if (!$path) {
            return null;
        }
        // if path is no absolute path
        if (!file_exists($path)) {
            $path = FileUtils::getModuleRootPath($module) . "/public/$path";
        }
        $path = realpath($path);
        if (!$path) {
            return null;
        }
        $url = Url::getApplicationUrl();
        if (
            !defined("FRAMELIX_ENTRY_POINT_FOLDER")
            || !defined("FRAMELIX_APP_ROOT")
            /** @phpstan-ignore-next-line */
            || FRAMELIX_ENTRY_POINT_FOLDER === FRAMELIX_APP_ROOT) {
            // if entry point is in app root folder instead of public folder of module
            // @codeCoverageIgnoreStart
            $relativePath = FileUtils::getRelativePathToBase($path, FRAMELIX_APP_ROOT);
            $url->appendPath($relativePath);
            // @codeCoverageIgnoreEnd
        } else {
            $pathModule = FileUtils::getModuleForPath($path);
            $pathModuleRoot = FileUtils::getModuleRootPath($pathModule) . "/public";
            $relativePath = FileUtils::getRelativePathToBase($path, $pathModuleRoot);
            if ($module === $pathModule) {
                $url->appendPath($relativePath);
            } else {
                $url->appendPath("@$pathModule/$relativePath");
            }
        }
        if ($antiCacheParameter) {
            $url->setParameter('t', filemtime($path));
        }
        $url->removeGlobalContextParameters();
        return $url;
    }

    /**
     * Get url to modules public folder
     * Could be the same as getApplicationUrl, depending on config
     * @param string $module
     * @return Url
     */
    public static function getModulePublicFolderUrl(string $module): self
    {
        $path = FileUtils::getModuleRootPath($module) . "/public";
        $url = Url::getApplicationUrl();
        $relativePath = '';
        // @codeCoverageIgnoreStart
        /** @phpstan-ignore-next-line */
        if (FRAMELIX_ENTRY_POINT_FOLDER === FRAMELIX_APP_ROOT) {
            $relativePath = FileUtils::getRelativePathToBase($path, FRAMELIX_APP_ROOT);
        }
        // @codeCoverageIgnoreEnd
        $url->appendPath($relativePath);
        return $url;
    }

    /**
     * Get application url
     * @return Url
     */
    public static function getApplicationUrl(): self
    {
        $url = Config::get('applicationHttps') ? "https" : "http";
        $url .= '://';
        $url .= str_replace("{host}", $_SERVER['HTTP_HOST'] ?? '', Config::get('applicationHost'));
        $url .= "/" . trim("/" . Config::get('applicationUrlBasePath'), "/");
        return self::create($url);
    }

    /**
     * Create current browser url
     * Return the same as ::create if no browser url header exist
     * Helpful in nested async requests when context is required
     * @return Url
     */
    public static function getBrowserUrl(): self
    {
        return self::create(Request::getHeader('http_x_browser_url'));
    }

    /**
     * Create url instance based on given url
     * @param string|Url|null $url
     * @param bool $keepGlobalContext If true, then always keep the global context parameters for the keys defined in config -> urlGlobalContextParameterKeys
     *  This is used to always keep this parameter in all generated urls
     * @return Url
     */
    public static function create(string|Url|null $url = null, bool $keepGlobalContext = true): self
    {
        $originalUrl = $url;
        if ($url instanceof Url) {
            $url = $url->getUrlAsString();
        }
        if (!$url) {
            $url = (Request::isHttps() ? 'https' : 'http') . "://"
                . ($_SERVER['HTTP_HOST'] ?? Config::get('applicationHost')) . $_SERVER['REQUEST_URI'];
        }
        $instance = new self();
        $instance->update($url, true);
        if ($keepGlobalContext && $originalUrl !== null) {
            $keepKeys = Config::get('urlGlobalContextParameterKeys');
            if (is_array($keepKeys)) {
                foreach ($keepKeys as $key) {
                    $instance->setParameter($key, Request::getGet($key));
                }
            }
        }
        return $instance;
    }

    /**
     * To string converts it to the complete url
     * @return string
     */
    public function __toString(): string
    {
        $url = "";
        if ($this->urlData['scheme'] ?? null) {
            $url .= $this->urlData['scheme'] . "://";
        }
        $hostPrefix = null;
        if ($this->urlData['user'] ?? null) {
            $url .= $this->urlData['user'];
            $hostPrefix = "@";
        }
        if ($this->urlData['pass'] ?? null) {
            $url .= ":" . $this->urlData['pass'];
            $hostPrefix = "@";
        }
        if ($this->urlData['host'] ?? null) {
            $url .= $hostPrefix . $this->urlData['host'];
        }
        if ($this->urlData['port'] ?? null) {
            $url .= ":" . $this->urlData['port'];
        }
        $url .= $this->getPathAndQueryString();
        if ($this->urlData['fragment'] ?? null) {
            $url .= "#" . $this->urlData['fragment'];
        }
        return $url;
    }

    /**
     * Get url
     * @return string
     */
    public function getUrlAsString(): string
    {
        return (string)$this;
    }

    /**
     * Set scheme (http/https)
     * @param string $str
     * @return self
     */
    public function setScheme(string $str): self
    {
        $this->urlData['scheme'] = $str;
        return $this;
    }

    /**
     * Get scheme (http/https)
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->urlData['scheme'] ?? null;
    }

    /**
     * Set username
     * @param string|null $str
     * @return self
     */
    public function setUsername(?string $str): self
    {
        $this->urlData['user'] = $str;
        return $this;
    }

    /**
     * Get username
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->urlData['user'] ?? null;
    }

    /**
     * Set password
     * @param string|null $str
     * @return self
     */
    public function setPassword(?string $str): self
    {
        $this->urlData['pass'] = $str;
        return $this;
    }

    /**
     * Get password
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->urlData['pass'] ?? null;
    }

    /**
     * Set host
     * @param string $str
     * @return self
     */
    public function setHost(string $str): self
    {
        $this->urlData['host'] = $str;
        return $this;
    }

    /**
     * Get host
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->urlData['host'] ?? null;
    }

    /**
     * Set port
     * @param int|null $port
     * @return self
     */
    public function setPort(?int $port): self
    {
        $this->urlData['port'] = $port;
        return $this;
    }

    /**
     * Get port
     * @return int|null
     */
    public function getPort(): ?int
    {
        return isset($this->urlData['port']) ? (int)$this->urlData['port'] : null;
    }

    /**
     * Set port
     * @param string $path
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->urlData['path'] = $path;
        return $this;
    }

    /**
     * Append given path to existing path
     * @param string $path
     * @return self
     */
    public function appendPath(string $path): self
    {
        if (!$path || $path === "/") {
            return $this;
        }
        $this->urlData['path'] = rtrim($this->getPath(), "/") . "/" . ltrim($path, "/");
        return $this;
    }

    /**
     * Get path
     * @return string
     */
    public function getPath(): string
    {
        return $this->urlData['path'] ?? '';
    }

    /**
     * Get path and query string
     * @return string
     */
    public function getPathAndQueryString(): string
    {
        $str = $this->getPath();
        if (is_array($this->urlData['queryParameters'] ?? null) && $this->urlData['queryParameters']) {
            $str .= "?" . http_build_query($this->urlData['queryParameters']);
        }
        return $str;
    }

    /**
     * Get all query parameters
     * @return mixed
     */
    public function getParameters(): mixed
    {
        return $this->urlData['queryParameters'] ?? null;
    }

    /**
     * Get query parameter
     * @param string $key
     * @return mixed
     */
    public function getParameter(string $key): mixed
    {
        return ArrayUtils::getValue($this->urlData['queryParameters'] ?? null, $key);
    }

    /**
     * Add multiple query parameters
     * @param array|null $parameters
     * @return self
     */
    public function addParameters(?array $parameters): self
    {
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                $this->setParameter($key, $value);
            }
        }
        return $this;
    }

    /**
     * Set query parameter
     * @param string $key
     * @param mixed $value Null will remove the key
     * @return self
     */
    public function setParameter(string $key, mixed $value): self
    {
        if ($value === null) {
            unset($this->urlData['queryParameters'][$key]);
            return $this;
        }
        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                $this->setParameter($key . "[$subKey]", $subValue);
            }
        } else {
            $this->urlData['queryParameters'][$key] = (string)$value;
        }
        return $this;
    }

    /**
     * Remove all query parameters
     * @return self
     */
    public function removeParameters(): self
    {
        $this->urlData['queryParameters'] = [];
        return $this;
    }

    /**
     * Remove query parameter
     * @param string $key
     * @return self
     */
    public function removeParameter(string $key): self
    {
        return $this->setParameter($key, null);
    }

    /**
     * Remove the global context parameter keys
     * Defined in config -> urlGlobalContextParameterKeys
     */
    public function removeGlobalContextParameters(): void
    {
        $keepKeys = Config::get('urlGlobalContextParameterKeys');
        if (is_array($keepKeys)) {
            foreach ($keepKeys as $key) {
                $this->removeParameter($key);
            }
        }
    }

    /**
     * Check if url has a parameter with the given value
     * @param mixed $value
     * @return bool
     */
    public function hasParameterWithValue(mixed $value): bool
    {
        if ($this->urlData['queryParameters'] ?? null) {
            $compareValue = (string)$value;
            if ($compareValue === '') {
                return false;
            }
            foreach ($this->urlData['queryParameters'] as $parameterValue) {
                if ($compareValue === $parameterValue) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Remove parameters where the value is equal to the given value
     * @param mixed $value
     * @return self
     */
    public function removeParameterByValue(mixed $value): self
    {
        if ($this->urlData['queryParameters'] ?? null) {
            $compareValue = (string)$value;
            foreach ($this->urlData['queryParameters'] as $key => $parameterValue) {
                if ($compareValue === $parameterValue) {
                    unset($this->urlData['queryParameters'][$key]);
                }
            }
        }
        return $this;
    }

    /**
     * Set hash
     * @param string|null $hash
     * @return self
     */
    public function setHash(?string $hash): self
    {
        if ($hash === null) {
            unset($this->urlData['fragment']);
            return $this;
        }
        $this->urlData['fragment'] = $hash;
        return $this;
    }

    /**
     * Get hash
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->urlData['fragment'] ?? null;
    }

    /**
     * Update this instance data with data from given url
     * @param string $url
     * @param bool $clearData If true, then delete all other urldata from this instance if not exist in $url
     * @return self
     */
    public function update(string $url, bool $clearData = false): self
    {
        $urlData = parse_url($url);
        $urlData['path'] = $urlData['path'] ?? '';
        if (isset($urlData['query'])) {
            parse_str($urlData['query'], $urlData['queryParameters']);
        }
        if ($clearData) {
            $this->urlData = $urlData;
        } else {
            $this->urlData = ArrayUtils::merge($this->urlData, $urlData);
        }
        return $this;
    }

    /**
     * Get language from current url if exist and app is configured to have multiple languages
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        $lang = null;
        if (Config::get('languageMultiple')) {
            $supportedLanguages = Config::get('languagesSupported');
            if ($supportedLanguages) {
                $relativeUrl = $this->getRelativePath(self::getApplicationUrl());
                foreach ($supportedLanguages as $language) {
                    if (str_starts_with($relativeUrl, "/$language/") || $relativeUrl === "/$language") {
                        $lang = $language;
                        break;
                    }
                }
            }
        }
        return $lang;
    }

    /**
     * Replace current url language with the new language
     * @param string $newLanguage
     */
    public function replaceLanguage(string $newLanguage): void
    {
        $foundLanguage = $this->getLanguage();
        $applicationUrl = self::getApplicationUrl();
        $relativeUrl = $this->getRelativePath($applicationUrl);
        if ($foundLanguage) {
            $relativeUrl = substr($relativeUrl, strlen($foundLanguage) + 1);
        }
        $this->setPath($applicationUrl->appendPath("/$newLanguage" . $relativeUrl)->getPath());
    }

    /**
     * Get relative path to other url
     * @param Url|null $otherUrl If not set, get full relative path of current url
     * @return string
     */
    public function getRelativePath(?self $otherUrl = null): string
    {
        $startFrom = 0;
        if ($otherUrl) {
            $applicationUrl = Url::getApplicationUrl();
            $startFrom = strlen($applicationUrl->urlData['path']);
            if (str_ends_with($applicationUrl->urlData['path'], "/")) {
                $startFrom--;
            }
        }
        return substr($this->urlData['path'], $startFrom);
    }

    /**
     * Redirect
     * @param int $code
     * @return never
     * @codeCoverageIgnore
     */
    public function redirect(int $code = 302): never
    {
        if (Request::isAsync()) {
            Response::header("x-redirect: " . $this->getUrlAsString());
        } else {
            http_response_code($code);
            Response::header("location: " . $this->getUrlAsString());
        }
        exit;
    }

    /**
     * Sign the current url - Add a signature parameter
     * @param bool $signWithCurrentUserToken If true, then sign with current user token, so this url can only be verified by the same user
     * @param int $maxLifetime Max url lifetime in seconds, set to 0 if unlimited
     * @return self
     */
    public function sign(bool $signWithCurrentUserToken = true, int $maxLifetime = 86400): self
    {
        $this->removeParameter('__s');
        if ($signWithCurrentUserToken) {
            $this->setParameter('__usertoken', UserToken::getByCookie()->token ?? '');
            $this->setParameter('__t', 1);
        }
        if ($maxLifetime > 0) {
            $this->setParameter('__expires', time() + $maxLifetime);
        }
        $hash = CryptoUtils::hash($this);
        $this->removeParameter("__usertoken");
        $this->setParameter('__s', $hash);
        return $this;
    }

    /**
     * Verify if the url is correctly signed
     * @param bool $throwError
     * @return bool
     * @throws Exception
     */
    public function verify(bool $throwError = true): bool
    {
        $originalData = $this->urlData['queryParameters'] ?? null;
        $sign = (string)$this->getParameter('__s');
        if (!$sign) {
            if ($throwError) {
                throw new Exception("URL has no signature", ErrorCode::URL_MISSING_SIGNATURE);
            }
            return false;
        }
        $token = (string)$this->getParameter('__t');
        $expires = (int)$this->getParameter('__expires');
        $this->removeParameter('__s');
        if ($token) {
            $this->removeParameter("__t");
            $this->removeParameter("__expires");
            $this->setParameter('__usertoken', UserToken::getByCookie()->token ?? '');
            $this->setParameter("__t", "1");
            if ($expires > 0) {
                $this->setParameter("__expires", $expires);
            }
        }
        $result = CryptoUtils::compareHash($this, $sign);
        if (!$result && $throwError) {
            throw new Exception("URL not correctly signed", ErrorCode::URL_INCORRECT_SIGNATURE);
        }
        if ($result && $expires > 0 && $expires < time()) {
            $result = false;
            if ($throwError) {
                throw new Exception("URL has expired", ErrorCode::URL_EXPIRED_SIGNATURE);
            }
        }
        $this->urlData['queryParameters'] = $originalData;
        return $result;
    }

    /**
     * Get json data
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getUrlAsString();
    }
}