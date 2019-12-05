<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubspotApplication;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class HubspotApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Hubspot
 */
final class HubspotApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '3cc4771e-deb7-4905-8e6b-d2**********';

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $this->mockRedirect(HubspotApplication::HUBSPOT_URL, self::CLIENT_ID, 'contacts');
        $hubspotApplication = self::$container->get('hbpf.application.hubspot');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $hubspotApplication->getKey(),
            'user',
            'token',
            self::CLIENT_ID
        );
        $this->pf($applicationInstall);
        $hubspotApplication->authorize($applicationInstall);
    }

}
