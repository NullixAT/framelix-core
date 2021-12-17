<?php

namespace Framelix\Framelix\Html;

use Exception;
use Framelix\Framelix\Url;

use function htmlentities;
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
            throw new Exception("Cannot generate include tag for  $url - Unsupported extension");
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
        $str = htmlentities($str);
        if ($nl2br) {
            $str = nl2br($str);
        }
        return $str;
    }
}