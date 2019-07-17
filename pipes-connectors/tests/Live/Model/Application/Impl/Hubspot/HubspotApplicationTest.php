<?php declare(strict_types=1);

namespace Tests\Live\Model\Application\Impl\Hubspot;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
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
     * @throws DateTimeException
     */
    public function testAutorize(): void
    {
        $app                           = self::$container->get('hbpf.application.hubspot');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token123',
            '39a37203-8278-4856-868e-02ae2e15959d',
            'd3cd7ff4-ebf3-4b12-8429-fc4a8d2aaeb0'
        );
        $this->pf($applicationInstall);
        $app->authorize($applicationInstall);
    }

}
