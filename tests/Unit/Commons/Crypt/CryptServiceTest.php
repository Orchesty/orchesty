<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Crypt;

use Hanaboso\PipesFramework\Commons\Crypt\CryptException;
use Hanaboso\PipesFramework\Commons\Crypt\CryptService;
use stdClass;
use Tests\KernelTestCaseAbstract;

/**
 * Class CryptServiceTest
 *
 * @package Tests\Unit\Commons\Crypt
 */
final class CryptServiceTest extends KernelTestCaseAbstract
{

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->cryptService = $this->container->get('hbpf.crypt.crypt_service');
    }

    /**
     * @covers CryptService::encrypt()
     * @covers CryptService::decrypt()
     */
    public function testEncryptAndDecrypt(): void
    {
        $arr   = [];
        $arr[] = 'Some random text';
        $arr[] = 'docker://dkr.hanaboso.net/pipes/pipes/php-dev:dev/php /opt/project/pf-bundles/vendor/phpunit/phpunit/phpunit --configuration /opt/project/pf-bundles/phpunit.xml.dist Tests\Unit\Commons\Crypt\CryptServiceProviderTest /opt/project/pf-bundles/tests/Unit/Commons/Crypt/CryptServiceProviderTest.php --teamcity';
        $arr[] = ['1', '2', 3, ['abc']];

        $stdClass        = new stdClass();
        $stdClass->true  = TRUE;
        $stdClass->false = FALSE;
        $stdClass->arr   = ['foo'];
        $arr[]           = $stdClass;

        foreach ($arr as $item) {
            $encrypted = $this->cryptService->encrypt($item);
            $decrypted = $this->cryptService->decrypt($encrypted);
            $this->assertEquals($item, $decrypted);
        }
    }

    /**
     * @covers CryptService::encrypt()
     * @covers CryptService::decrypt()
     */
    public function testEncryptAndDecryptFail(): void
    {
        $str = 'Some random text';

        $encrypted = $this->cryptService->encrypt($str);

        $this->expectException(CryptException::class);
        $this->expectExceptionCode(CryptException::UNKNOWN_PREFIX);

        $this->cryptService->decrypt('abc' . $encrypted);
    }

    /**
     * @covers CryptService::encrypt()
     * @covers CryptService::decrypt()
     */
    public function testEncryptAndDecrypt2(): void
    {
        $str          = 'asdf12342~!@#$%^&*()_+{}|:"<>?[]\;,./';
        $encryptedStr = $this->cryptService->encrypt($str);

        $arr          = ['key' => 'val', 'str' => $encryptedStr];
        $encryptedArr = $this->cryptService->encrypt($arr);

        $decryptedArr = $this->cryptService->decrypt($encryptedArr);
        $decryptedStr = $this->cryptService->decrypt($decryptedArr['str']);

        $this->assertEquals($str, $decryptedStr);
        $this->assertEquals($arr, $decryptedArr);
    }

}