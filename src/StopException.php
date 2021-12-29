<?php

namespace Framelix\Framelix;

/**
 * StopException
 * It is used to stop script execution gracefully to be able to use it even in unit tests
 */
class StopException extends \Exception
{
}