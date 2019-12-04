<?php declare(strict_types=1);

namespace Tests\Controller;

use Exception;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class StatisticsControllerTest
 *
 * @package Tests\Controller
 */
final class StatisticsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetApplicationsBasicData(): void
    {
        $this->createApps();

        self::$client->request('GET', '/statistics/applications');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($response->getContent(), file_get_contents(sprintf('%s/data/applications.json', __DIR__)));
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationsBasicDataNotFound(): void
    {
        $this->createApps();

        self::$client->request('GET', '/statistics/applicationssss');
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationsUsers(): void
    {
        $this->createApps();

        self::$client->request('GET', '/statistics/applications/hubspot');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($response->getContent(), file_get_contents(sprintf('%s/data/appUsers.json', __DIR__)));
    }

    /**
     * --------------------------------- HELPERS ------------------------
     */

    /**
     * @throws Exception
     */
    private function createApps(): void
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setKey('hubspot');
        $applicationInstall->setUser('user2');
        $applicationInstall->setExpires(DateTimeUtils::getUtcDateTime('- 1 Day'));
        $applicationInstall3 = new ApplicationInstall();
        $applicationInstall3->setKey('hubspot');
        $applicationInstall3->setUser('user3');
        $applicationInstall3->setExpires(DateTimeUtils::getUtcDateTime('+ 1 Day'));
        $applicationInstall2 = new ApplicationInstall();
        $applicationInstall2->setKey('mailchimp');
        $applicationInstall2->setUser('user2');
        $applicationInstall4 = new ApplicationInstall();
        $applicationInstall4->setKey('shipstation');
        $applicationInstall4->setUser('user2');
        $applicationInstall4->setExpires(DateTimeUtils::getUtcDateTime('+ 1 Day'));
        $this->dm->persist($applicationInstall);
        $this->dm->persist($applicationInstall2);
        $this->dm->persist($applicationInstall3);
        $this->dm->persist($applicationInstall4);
        $this->dm->flush();
    }

}
