<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Config;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function preg_replace;
use function strtoupper;
use function substr;

/**
 * Request utilities for frequent tasks
 */
class Request
{
    /**
     * Request body data
     * Public to simulate in unit tests
     * @var array
     */
    public static mixed $requestBodyData = [];

    /**
     * Get client ip
     * @return string
     */
    public static function getClientIp(): string
    {
        $ip = Config::get('clientIpFixed') ?? $_SERVER[Config::get('clientIpKey')] ?? "0.0.0.0";
        // sanitize ip as it can be manipulated by the client if custom header is used
        return substr(preg_replace("~[^0-9\.]~", "", $ip), 0, 15);
    }

    /**
     * Get a value from the current request body, assuming that current request contains json data in body
     * @param string|string[]|null $key Null is complete body, could also be a key in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @return mixed
     */
    public static function getBody(mixed $key = null): mixed
    {
        // this cannot be unit tested as php://input is read only
        // so we simulate $requestBodyData in unit tests
        // @codeCoverageIgnoreStart
        if (!ArrayUtils::keyExists(self::$requestBodyData, "data")) {
            self::$requestBodyData['data'] = null;
            if (Request::getHeader('content_type') !== 'application/json') {
                return null;
            }
            self::$requestBodyData['data'] = JsonUtils::readFromFile("php://input");
        }
        // @codeCoverageIgnoreEnd
        if ($key === null) {
            return self::$requestBodyData['data'];
        }
        return ArrayUtils::getValue(self::$requestBodyData['data'], $key);
    }

    /**
     * Get a $_GET value
     * @param string|string[] $key Could be a key in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @return mixed
     */
    public static function getGet(mixed $key): mixed
    {
        return ArrayUtils::getValue($_GET, $key);
    }

    /**
     * Get a $_POST value
     * @param string|string[] $key Could be a key in any depth, example: foo[bar][depth][deeper] or ['foo', 'bar', 'depth', 'deeper']
     * @return mixed
     */
    public static function getPost(mixed $key): mixed
    {
        return ArrayUtils::getValue($_POST, $key);
    }

    /**
     * Get specific header from $_SERVER
     * @param string $key
     * @return string|null
     */
    public static function getHeader(string $key): ?string
    {
        return $_SERVER[strtoupper($key)] ?? null;
    }

    /**
     * Is current request with ajax
     * @return bool
     */
    public static function isAsync(): bool
    {
        return self::getHeader('http_x_requested_with') === 'xmlhttprequest';
    }

}