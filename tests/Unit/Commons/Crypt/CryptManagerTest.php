<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Crypt;

use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use stdClass;
use Tests\KernelTestCaseAbstract;

/**
 * Class CryptManagerTest
 *
 * @package Tests\Unit\Commons\Crypt
 */
final class CryptManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers CryptManager::encrypt()
     * @covers CryptManager::decrypt()
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
            $encrypted = CryptManager::encrypt($item);
            $decrypted = CryptManager::decrypt($encrypted);
            $this->assertEquals($item, $decrypted);
        }
    }

}