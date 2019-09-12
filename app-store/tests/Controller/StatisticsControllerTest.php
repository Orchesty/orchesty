<?php declare(strict_types=1);

namespace Tests\Controller;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class StatisticControllerTest
 *
 * @package Tests\Controller
 */
final class StatisticsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws DateTimeException
     */
    private function createApps(): void
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setKey('hubspot');
        $applicationInstall->setUser('user2');
        $applicationInstall3 = new ApplicationInstall();
        $applicationInstall3->setKey('hubspot');
        $applicationInstall3->setUser('user3');
        $applicationInstall3->setExpires(DateTimeUtils::getUtcDateTime());
        $applicationInstall2 = new ApplicationInstall();
        $applicationInstall2->setKey('mailchimp');
        $applicationInstall2->setUser('user2');
        $applicationInstall4 = new ApplicationInstall();
        $applicationInstall4->setKey('shipstation');
        $applicationInstall4->setUser('user2');
        $applicationInstall4->setExpires(DateTimeUtils::getUtcDateTime());
        $this->dm->persist($applicationInstall);
        $this->dm->persist($applicationInstall2);
        $this->dm->persist($applicationInstall3);
        $this->dm->persist($applicationInstall4);
        $this->dm->flush();
    }

    /**
     *
     */
    public function testGetApplicationsBasicData(): void
    {
        $this->createApps();

        self::$client->request('GET', '/statistics/applicationssss');
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(404, $response->getStatusCode());

        self::$client->request('GET', '/statistics/applications');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($response->getContent(), file_get_contents(sprintf('%s/data/applications.json', __DIR__)));
    }

    /**
     *
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

}
