<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Hubspot;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class HubspotApplicationTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Hubspot
 */
final class HubspotApplicationTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $app                = self::getContainer()->get('hbpf.application.hub-spot');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'token123',
            '3cc4771e-deb7-4905-8e6b-d2**********',
            '5ef27043-34cc-43d1-9751-65**********',
        );
        $app->authorize($applicationInstall);
        self::assertFake();
    }

}
