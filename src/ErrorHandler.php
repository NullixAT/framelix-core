<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\Email;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Throwable;

use function error_get_last;
use function explode;
use function file_put_contents;
use function htmlentities;
use function http_response_code;
use function implode;
use function in_array;
use function json_encode;
use function ksort;
use function php_sapi_name;
use function preg_match_all;
use function preg_quote;
use function str_replace;
use function syslog;
use function time;
use function urlencode;

use const DIRECTORY_SEPARATOR;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_ERROR;
use const E_PARSE;
use const E_STRICT;
use const LOG_INFO;

/**
 * Framelix exception and error handling
 */
class ErrorHandler
{
    /**
     * On shutdown
     * Called when system is shutting down, the real last script to run
     * Checks for errors and display them
     * @codeCoverageIgnore
     */
    public static function onShutdown(): void
    {
        if ($error = error_get_last()) {
            if (in_array(
                $error["type"],
                [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT]
            )) {
                self::onException(new Exception($error["message"], ErrorCode::PHP_ERROR));
            }
        }
    }

    /**
     * Throwable to json
     * @param Throwable $e
     * @return array
     */
    public static function throwableToJson(Throwable $e): array
    {
        if (Config::get('errorLogExtended') || Config::get('devMode')) {
            $server = $_SERVER;
            ksort($server);
            $post = $_POST;
            ksort($post);
            $session = $_SESSION ?? [];
            ksort($session);
            $cookie = $_COOKIE;
            ksort($cookie);
        } else {
            $server = 'Available with extended log only';
            $post = 'Available with extended log only';
            $session = 'Available with extended log only';
            $cookie = 'Available with extended log only';
        }
        return [
            'time' => time(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'traceSimple' => explode("\n", $e->getTraceAsString()),
            'traceExtended' => $e->getTrace(),
            'additionalData' => [
                'user' => User::get() ? User::get()->email . " #" . User::get() : null,
                'server' => $server,
                'post' => $post,
                'session' => $session,
                'cookie' => $cookie,
            ]
        ];
    }

    /**
     * Show error as html from exception data
     * @param array $logData
     * @param bool $forceShowDetails If true, then all exception data will be shown. If false, this info is only visible in devMode
     */
    public static function showErrorFromExceptionLog(array $logData, bool $forceShowDetails = false): void
    {
        if (!Config::isDevMode() && !$forceShowDetails) {
            echo '<pre style="color:red; font-weight: bold">' . htmlentities($logData['message']) . '</pre>';
        } else {
            $id = RandomGenerator::getRandomHtmlId();
            $html = [
                'title' => htmlentities($logData['message']) . ' in ' . $logData['file'] . '(' . $logData['line'] . ')',
                'trace' => implode('</pre><pre class="framelix-erorr-log-trace">', $logData['traceSimple'])
            ];
            $root = str_replace("/", DIRECTORY_SEPARATOR, FRAMELIX_APP_ROOT);
            foreach ($html as $key => $value) {
                preg_match_all(
                    "~(" . preg_quote($root, "~") . "[^\s]+)([(:]| on line )([0-9]+)\)*~i",
                    $value,
                    $matches
                );
                if ($matches[0] ?? null) {
                    foreach ($matches[0] as $matchKey => $match) {
                        $value = str_replace(
                            $match,
                            '<a href="phpstorm://open?' . urlencode(
                                "file=" . $matches[1][$matchKey] . '&line=' . $matches[3][$matchKey]
                            ) . '">' . $match . '</a>',
                            $value
                        );
                    }
                }
                $html[$key] = $value;
            }
            ?>
            <div id="<?= $id ?>" class="framelix-erorr-log">
                <small><?= DateTime::anyToFormat($logData['time'] ?? null, "d.m.Y H:i:s") ?></small>
                <pre class="framelix-erorr-log-title"><?= $html['title'] ?></pre>
                <pre class="framelix-erorr-log-trace"><?= $html['trace'] ?></pre>
                <pre class="framelix-erorr-log-json"><?= JsonUtils::encode(
                        $logData['additionalData'] ?? null,
                        true
                    ) ?></pre>
            </div>
            <style>
              .framelix-erorr-log {
                padding: 10px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.3);
              }

              .framelix-erorr-log-title {
                color: var(--color-error-text, red);
                font-weight: bold;
                max-width: 100%;
                overflow-x: auto;
                white-space: pre-line;
                margin: 0;
                font-size: 0.9rem;
              }

              .framelix-erorr-log-trace,
              .framelix-erorr-log-json {
                max-width: 100%;
                overflow-x: auto;
                white-space: pre-wrap;
                text-indent: -27px;
                padding-left: 27px;
                display: block;
                margin: 0;
                font-size: 0.8rem;
              }
            </style>
            <script>
              (function () {
                const errorData = <?=json_encode($logData)?>;
                console.log('Framelix Error', errorData)
              })()
            </script>
            <?php
        }
    }

    /**
     * Save error log to disk
     * @param array $logData
     */
    public static function saveErrorLogToDisk(array $logData): void
    {
        if (Config::get('errorLogDisk')) {
            $path = FRAMELIX_APP_ROOT . "/logs/error-" . time() . "-" . RandomGenerator::getRandomString(3, 6) . ".php";
            file_put_contents($path, "<?php if(!defined(\"FRAMELIX_MODULE\")){die();} ?>" . json_encode($logData));
        }
    }

    /**
     * Save error log into syslog
     * @param array $logData
     * @codeCoverageIgnore
     */
    public static function logErrorInSyslog(array $logData): void
    {
        if (Config::get('errorLogSyslog')) {
            syslog(LOG_INFO, $logData['message']);
        }
    }

    /**
     * Send error log email
     * @param array $logData
     * @codeCoverageIgnore
     */
    public static function sendErrorLogEmail(array $logData): void
    {
        $email = Config::get('errorLogEmail');
        if ($email && Email::isAvailable()) {
            $body = '<h2 style="color:red">' . htmlentities($logData['message']) . '</h2>';
            $body .= '<pre>' . htmlentities(implode("\n", $logData['traceSimple'])) . '</pre>';
            $body .= '<pre>' . htmlentities(JsonUtils::encode($logData['additionalData'] ?? null, true)) . '</pre>';
            Email::send(
                'ErrorLog: ' . $logData['message'],
                $body,
                $email
            );
        }
    }

    /**
     * On exception
     * Is called when an exception occurs
     * @param Throwable $e
     * @codeCoverageIgnore
     */
    public static function onException(Throwable $e): void
    {
        // a stop exception does nothing, it is a gracefull expected stop of script execution
        if ($e instanceof StopException) {
            return;
        }
        $buffer = Buffer::getAll();
        $logData = self::throwableToJson($e);
        $logData['buffer'] = $buffer;
        try {
            self::saveErrorLogToDisk($logData);
            self::logErrorInSyslog($logData);
            self::sendErrorLogEmail($logData);
        } catch (Throwable $e) {
        }
        // on command line, output raw exception data
        if (php_sapi_name() === 'cli') {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
            return;
        }
        // on server, return with error response code and let the views handles the output
        http_response_code(500);
        if (!View::$activeView) {
            ErrorHandler::showErrorFromExceptionLog($logData);
        } else {
            try {
                View::$activeView->onException($logData);
            } catch (Throwable $subE) {
                if ($subE instanceof StopException) {
                    return;
                }
                Buffer::clear();
                echo '<h2 style="color: red">There is an error in the error handler</h2>';
                echo '<h3 style="color: red">Original Error</h3>';
                ErrorHandler::showErrorFromExceptionLog($logData);
                echo '<h3 style="color: red">Errow while handling original error</h3>';
                ErrorHandler::showErrorFromExceptionLog(self::throwableToJson($subE));
            }
        }
    }

    /**
     * On php error
     * Is called when a php error occurs
     * @param mixed $errno
     * @param mixed $errstr
     * @param mixed $errfile
     * @param mixed $errline
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public static function onError(mixed $errno, mixed $errstr, mixed $errfile, mixed $errline): bool
    {
        // check if error was suppressed with @
        // ugly but possible and done by some 3rd party libraries
        if (!(bool)(error_reporting() & $errno)) {
            return true;
        }
        throw new Exception($errstr);
    }
}