<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
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

    private const CLIENT_ID = '39a37203-8278-4856-868e-02ae2e15959d';

    /**
     * @throws DateTimeException
     */
    public function testAutorize(): void
    {
        $this->mockRedirect(HubspotApplication::HUBSPOT_URL, self::CLIENT_ID, 'contacts');
        $hubspotApplication = self::$container->get('hbpf.application.hubspot');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            'hubspot',
            'user',
            'token',
            self::CLIENT_ID
        );
        $this->pf($applicationInstall);
        $hubspotApplication->authorize($applicationInstall);
    }

}
