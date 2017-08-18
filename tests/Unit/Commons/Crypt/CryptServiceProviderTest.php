<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Crypt;

use Hanaboso\PipesFramework\Commons\Crypt\CryptException;
use Hanaboso\PipesFramework\Commons\Crypt\CryptInterface;
use Hanaboso\PipesFramework\Commons\Crypt\CryptServiceProvider;
use Tests\KernelTestCaseAbstract;

/**
 * Class CryptServiceProviderTest
 *
 * @package Tests\Unit\Commons\Crypt
 */
final class CryptServiceProviderTest extends KernelTestCaseAbstract
{

    /**
     * @var CryptServiceProvider
     */
    private $cryptServiceProvider;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->cryptServiceProvider = $this->container->get('hbpf.crypt.crypt_service_provider');
    }

    /**
     * @covers CryptServiceProvider::getServiceForEncryption()
     */
    public function testGetServiceForEncryption(): void
    {
        $result = $this->cryptServiceProvider->getServiceForEncryption();

        $this->assertInstanceOf(CryptInterface::class, $result);
    }

    /**
     * @covers CryptServiceProvider::getServiceForDecryption()
     */
    public function testGetServiceForDecryption(): void
    {
        $result = $this->cryptServiceProvider->getServiceForDecryption(CryptServiceProvider::DEFAULT);

        $this->assertInstanceOf(CryptInterface::class, $result);
    }

    /**
     * @covers CryptServiceProvider::getServiceForDecryption()
     */
    public function testGetServiceForDecryptionFail(): void
    {
        $this->expectException(CryptException::class);
        $this->expectExceptionCode(CryptException::UNKNOWN_PREFIX);

        $this->cryptServiceProvider->getServiceForDecryption('abc');
    }

}