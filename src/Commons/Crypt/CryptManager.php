<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Crypt;

/**
 * Class CryptManager
 *
 * @package Hanaboso\PipesFramework\Commons\Crypt
 */
class CryptManager
{

    public const PREFIX_LENGTH = 3;

    /**
     * Encrypt data by concrete crypt service impl
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function encrypt($data): string
    {
        return CryptService::encrypt($data);
    }

    /**
     * Tries to identify which crypt service to use for decryption by prefix and passes hash to it
     *
     * @param string $data
     *
     * @return mixed
     * @throws CryptException
     */
    public static function decrypt(string $data)
    {
        $prefix = substr($data, 0, self::PREFIX_LENGTH);

        switch ($prefix) {
            // add new implementation of crypt services as you wish
            case CryptService::PREFIX:
                return CryptService::decrypt($data);
            default:
                throw new CryptException('Unknown crypt service prefix', CryptException::UNKNOWN_PREFIX);
        }
    }

}