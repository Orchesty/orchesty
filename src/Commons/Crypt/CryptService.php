<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:38 PM
 */

namespace Hanaboso\PipesFramework\Commons\Cryptography;

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

    /**
     * @var string
     */
    private $key;

    /**
     * CryptService constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        // TODO generate key
        $this->key = $key;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function encrypt($data): string
    {
        $hiddenString = new HiddenString(serialize($data));

        return CryptServiceProvider::DEFAULT . Crypto::encrypt($hiddenString, $this->buildEncryptionKey());
    }

    /**
     * @param string $hash
     *
     * @return mixed
     * @throws CryptException
     */
    public function decrypt(string $hash)
    {
        if (strpos($hash, CryptServiceProvider::DEFAULT) !== 0) {
            throw new CryptException('Unknown prefix in hash.', CryptException::UNKNOWN_PREFIX);
        }

        $hiddenString = Crypto::decrypt(substr($hash, 2), $this->buildEncryptionKey());

        return unserialize($hiddenString->getString());
    }

    /**
     * @return EncryptionKey
     */
    private function buildEncryptionKey(): EncryptionKey
    {
        $hiddenString = new HiddenString(KeyFactory::getKeyDataFromString(hex2bin($this->key)));

        return new EncryptionKey($hiddenString);
    }

}