<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Crypt;

/**
 * Class CryptServiceProvider
 *
 * @package Hanaboso\PipesFramework\Commons\Crypt
 */
class CryptServiceProvider
{

    public const DEFAULT = '00_';

    /**
     * @var CryptInterface[]
     */
    private $cryptServices;

    /**
     * CryptServiceProvider constructor.
     *
     * @param CryptService $cryptService
     */
    public function __construct(CryptService $cryptService)
    {
        $this->cryptServices = [
            self::DEFAULT => $cryptService,
        ];
    }

    /**
     * @return CryptInterface
     */
    public function getServiceForEncryption(): CryptInterface
    {
        return $this->findServiceByPrefix(self::DEFAULT);
    }

    /**
     * @param string $prefix
     *
     * @return CryptInterface
     */
    public function getServiceForDecryption(string $prefix): CryptInterface
    {
        return $this->findServiceByPrefix($prefix);
    }

    /**
     * @param string $prefix
     *
     * @return CryptInterface
     * @throws CryptException
     */
    private function findServiceByPrefix(string $prefix): CryptInterface
    {
        if (isset($this->cryptServices[$prefix])) {
            return $this->cryptServices[$prefix];
        }

        throw new CryptException(
            sprintf('Cannot find suitable crypt service. Unknown prefix "%s"', $prefix),
            CryptException::UNKNOWN_PREFIX
        );
    }

}