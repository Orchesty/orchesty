<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\HbPFAppStore\Handler\StatisticsHandler;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use HbPFAppStoreTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StatisticsControllerTest
 *
 * @package HbPFAppStoreTests\Controller
 */
final class StatisticsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\StatisticsController
     * @covers \Hanaboso\HbPFAppStore\Controller\StatisticsController::getApplicationsBasicDataAction
     * @covers \Hanaboso\HbPFAppStore\Handler\StatisticsHandler::getApplicationsBasicData
     * @covers \Hanaboso\HbPFAppStore\Model\StatisticsManager::getApplicationsBasicData
     *
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
     * @covers \Hanaboso\HbPFAppStore\Controller\StatisticsController
     *
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
     * @covers \Hanaboso\HbPFAppStore\Controller\StatisticsController::getApplicationsBasicDataAction
     */
    public function testGetApplicationBasicDataErr(): void
    {
        $this->mockStatisticHandlerException('getApplicationsBasicData');

        $response = (array) $this->sendGet('/statistics/applications');
        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\StatisticsController::getApplicationsUsersAction
     * @covers \Hanaboso\HbPFAppStore\Handler\StatisticsHandler
     * @covers \Hanaboso\HbPFAppStore\Handler\StatisticsHandler::getApplicationsUsers
     * @covers \Hanaboso\HbPFAppStore\Model\StatisticsManager
     * @covers \Hanaboso\HbPFAppStore\Model\StatisticsManager::getApplicationsUsers
     *
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
     * @covers \Hanaboso\HbPFAppStore\Controller\StatisticsController::getApplicationsUsersAction
     */
    public function testGetApplicationUserErr(): void
    {
        $this->mockStatisticHandlerException('getApplicationsUsers');

        $response = (array) $this->sendGet('/statistics/applications/hubspot');
        self::assertEquals(500, $response['status']);
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

    /**
     * @param string $fn
     */
    private function mockStatisticHandlerException(string $fn): void
    {
        $mock = self::createPartialMock(StatisticsHandler::class, [$fn]);
        $mock->expects(self::any())->method($fn)->willThrowException(new MongoDBException());
        self::$container->set('hbpf._application.handler.statistics', $mock);
    }

}
