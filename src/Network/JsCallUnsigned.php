<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\View;

/**
 * Js Call with data passed to javascript FramelixApi.callPhpMethod
 * Example PHP Method:
 * public static function onJsCall(JsCall $jsCall): void {}
 */
class JsCallUnsigned extends JsCall
{
    /**
     * Get an unsigned url to point to the callPhpMethod() function
     * @param string $phpMethod
     * @param string $action
     * @param array|null $additionalUrlParameters Additional array parameters to pass by
     * @return string
     */
    public static function getCallUrl(
        string $phpMethod,
        string $action,
        ?array $additionalUrlParameters = null
    ): string {
        return View::getUrl(
            View\Api::class,
            ['requestMethod' => 'callPhpMethod']
        )->setParameter('phpMethod', $phpMethod)
            ->setParameter('action', $action)
            ->addParameters($additionalUrlParameters)
            ->getUrlAsString();
    }
}