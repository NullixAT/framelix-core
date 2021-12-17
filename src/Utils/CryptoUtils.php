<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\Config;

use function hash;
use function is_array;
use function is_object;

/**
 * Crypto Utils
 */
class CryptoUtils
{
    /**
     * Simple data hashing - Choose $algo depending on your security needs
     * This function is mainly considered for very trivial things like url hashs and stuff that is not relevant for security
     * For passwords always use password_hash() and password_verify()
     * @param mixed $data Any data, will be converted to json before hashing if is array/object
     * @param string $saltId
     * @param string $algo The hash algo to use, see hash_algos()
     * @return string
     */
    public static function hash(mixed $data, string $saltId = 'general', string $algo = "md5"): string
    {
        if (is_array($data) || is_object($data)) {
            $data = JsonUtils::encode($data);
        }
        return hash(
            $algo,
            $data . Config::get("salts[$saltId]", true)
        );
    }

    /**
     * Compare if given data equals the given hash string
     * @param mixed $data Any data, will be converted to json before hashing if is array/object
     * @param string $hash The hash to compare against
     * @param string $saltId
     * @param string $algo The hash algo to use, see hash_algos()
     * @return bool
     */
    public static function compareHash(
        mixed $data,
        string $hash,
        string $saltId = 'general',
        string $algo = "md5"
    ): bool {
        $expectedHash = self::hash($data, $saltId, $algo);
        return $expectedHash === $hash;
    }
}