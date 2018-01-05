<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:38 PM
 */

namespace Hanaboso\PipesFramework\Commons\Crypt;

use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Class CryptService
 *
 * @package Hanaboso\PipesFramework\Commons\Crypt
 */
class CryptService implements CryptInterface
{

    public const PREFIX = '00_';

    private const SECRET_KEY = '31400300e703474eae51cc556068614dbd2b14aff0b40e1b6e2311674180245fa653be204bc4f78b400c86daf9629fac76eda2d05e5f52cce7581257c5fbd44a18cf1060afaf19b8ee8c1dca7a95f302907cfa3ace6375bc8abcd4b935bad8594565b9b6';

    /**
     * @param mixed $data
     *
     * @return string
     */
    public static function encrypt($data): string
    {
        $hiddenString = new HiddenString(serialize($data));

        return self::PREFIX . Crypto::encrypt($hiddenString, self::buildEncryptionKey());
    }

    /**
     * @param string $hash
     *
     * @return mixed
     * @throws CryptException
     */
    public static function decrypt(string $hash)
    {
        if (strpos($hash, self::PREFIX) !== 0) {
            throw new CryptException('Unknown prefix in hash.', CryptException::UNKNOWN_PREFIX);
        }

        $hiddenString = Crypto::decrypt(substr($hash, strlen(self::PREFIX)), self::buildEncryptionKey());

        return unserialize($hiddenString->getString());
    }

    /**
     * @return EncryptionKey
     */
    private static function buildEncryptionKey(): EncryptionKey
    {
        $hiddenString = new HiddenString(KeyFactory::getKeyDataFromString(hex2bin(self::SECRET_KEY)));

        return new EncryptionKey($hiddenString);
    }

}