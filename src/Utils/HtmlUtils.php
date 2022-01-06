<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Url;

use function html_entity_decode;
use function htmlentities;
use function nl2br;
use function str_ends_with;

/**
 * Html utilities for frequent tasks
 */
class HtmlUtils
{
    /**
     * Get include tag for given url
     * @param Url $url
     * @return string
     */
    public static function getIncludeTagForUrl(
        Url $url
    ): string {
        if (str_ends_with($url->urlData['path'], ".css")) {
            return '<link rel="stylesheet" media="all" href="' . $url . '">';
        } elseif (str_ends_with($url->urlData['path'], ".js")) {
            return '<script src="' . $url . '"></script>';
        } else {
            throw new Exception(
                "Cannot generate include tag for  $url - Unsupported extension",
                ErrorCode::HTMLUTILS_INCLUDE_INVALID_EXTENSION
            );
        }
    }

    /**
     * Escape a given string to be safe for user inputs
     * @param mixed $str
     * @param bool $nl2br Line feeds to <br/>
     * @return string
     */
    public static function escape(mixed $str, bool $nl2br = false): string
    {
        $str = htmlentities($str, encoding: 'UTF-8');
        if ($nl2br) {
            $str = nl2br($str);
        }
        return $str;
    }

    /**
     * Unescape a given string to raw text instead of html (opposite of escape())
     * @param mixed $str
     * @return string
     */
    public static function unescape(mixed $str): string
    {
        return html_entity_decode($str, encoding: 'UTF-8');
    }
}