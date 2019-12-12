<?php declare(strict_types=1);

namespace Tests\Live\Model\Application\Impl\Hubspot;

use Exception;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class HubspotApplicationTest
 *
 * @package Tests\Live\Model\Application\Impl\Hubspot
 */
final class HubspotApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $app                = self::$container->get('hbpf.application.hubspot');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token123',
            '3cc4771e-deb7-4905-8e6b-d2**********',
            '5ef27043-34cc-43d1-9751-65**********'
        );
        $this->pf($applicationInstall);
        //        $app->authorize($applicationInstall);
        self::assertEmpty([]);
    }

}
