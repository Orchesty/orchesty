<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Crypt;

/**
 * Class CryptManager
 *
 * @package Hanaboso\PipesFramework\Commons\Crypt
 */
class CryptManager
{

    /**
     * @var CryptServiceProvider
     */
    private $cryptServiceProvider;

    /**
     * CryptManager constructor.
     *
     * @param CryptServiceProvider $cryptServiceProvider
     */
    public function __construct(CryptServiceProvider $cryptServiceProvider)
    {
        $this->cryptServiceProvider = $cryptServiceProvider;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function encrypt($data): string
    {
        return $this->cryptServiceProvider->getServiceForEncryption()->encrypt($data);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function decrypt(string $data)
    {
        return $this->cryptServiceProvider->getServiceForDecryption(substr($data, 0, 3))->decrypt($data);
    }

}