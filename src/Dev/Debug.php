<?php

namespace Framelix\Framelix\Dev;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;

use function var_dump;

/**
 * Debug helper for quick dumps'n stuff
 */
class Debug
{
    /**
     * Dump data
     * @param mixed $data
     * @param bool $renderAsHtml If true then display content as formatted html and do console log in browser
     */
    public static function dump(mixed $data, bool $renderAsHtml = true): void
    {
        if (!$renderAsHtml || Request::isCli()) {
            var_dump($data);
            return;
        }
        $id = RandomGenerator::getRandomHtmlId();
        echo '<div id="' . $id . '" class="framelix-debug-data" style="background: #f5f5f5; color:#333; font-family: monospace; font-size: 14px; line-height: 1.2; padding:10px; border:1px solid #aaa;box-shadow: rgba(0,0,0,0.2); margin: 3px; white-space: pre; overflow: auto; max-width: 100vw; box-sizing: border-box"></div>';
        echo '<script>(function (){let data = ' . JsonUtils::encode(
                $data
            ) . '; console.log(data); document.getElementById("' . $id . '").innerHTML = JSON.stringify(data,null, 2)})()</script>';
    }
}