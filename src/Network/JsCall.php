<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\View;
use ReflectionClass;

use function class_exists;
use function count;
use function explode;
use function method_exists;
use function preg_replace;
use function str_contains;
use function strlen;
use function trim;

/**
 * Js Call with data passed to javascript FramelixApi.callPhpMethod
 * Example PHP Method:
 * public static function onJsCall(JsCall $jsCall): void {}
 */
class JsCall
{
    /**
     * The result to return
     * @var mixed
     */
    public mixed $result = null;

    /**
     * Get signed url to point to the callPhpMethod() function
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
            ->sign()
            ->getUrlAsString();
    }

    /**
     * Constructor
     * @param string $action The action passed to FramelixApi.callPhpMethod
     * @param mixed $parameters The parameters passed to FramelixApi.callPhpMethod
     */
    public function __construct(
        public string $action,
        public mixed $parameters
    ) {
    }

    /**
     * Call given callable method and passing this instance as parameter
     * Does verify the target function if it accepts a JsCall parameter
     * @param string $callableMethod
     * @return mixed The result of the invoked call
     */
    public function call(string $callableMethod): mixed
    {
        // validate if the requested php method exist and accept valid parameters
        $phpMethod = preg_replace("~[^a-z0-9_\\\\:]~i", "", $callableMethod);
        if (!str_contains($phpMethod, "::")) {
            $phpMethod .= "::onJsCall";
        }
        $reflectionMethod = null;
        $split = explode("::", $phpMethod);
        if ($split[0] && class_exists($split[0])) {
            if (method_exists($split[0], $split[1])) {
                $reflection = new ReflectionClass($split[0]);
                $method = $reflection->getMethod($split[1]);
                if ($method->isStatic()) {
                    $parameters = $method->getParameters();
                    if (count($parameters) === 1) {
                        $parameter = $parameters[0];
                        if ($parameter->getType()->getName() === JsCall::class) {
                            $reflectionMethod = $method;
                        }
                    }
                }
            }
        }
        if (!$reflectionMethod) {
            throw new Exception('Invalid php method', ErrorCode::API_INVALID_METHOD);
        }
        Buffer::start();
        $reflectionMethod->invoke(null, $this);
        $output = Buffer::get();
        if (strlen(trim($output)) > 0) {
            if (isset($this->result)) {
                throw new Exception("Cannot mix buffer output and \$jsCall->result", ErrorCode::API_MIXED_OUTPUT);
            }
            return $output;
        }
        return $this->result;
    }
}