<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\Applinth\Handler\StatisticsHandler;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class StatisticsControllerTest
 *
 * @package ApplinthTests\Controller
 *
 * @covers  \Hanaboso\Applinth\Controller\StatisticsController
 * @covers  \Hanaboso\Applinth\Handler\StatisticsHandler
 * @covers  \Hanaboso\Applinth\Manager\StatisticsManager
 */
final class StatisticsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::getApplicationsBasicDataAction
     * @covers \Hanaboso\Applinth\Handler\StatisticsHandler::getApplicationsBasicData
     * @covers \Hanaboso\Applinth\Manager\StatisticsManager::getApplicationsBasicData
     *
     * @throws Exception
     */
    public function testGetApplicationsBasicData(): void
    {
        $this->createApps();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/applicationRequest.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::getApplicationsBasicDataAction
     *
     * @throws Exception
     */
    public function testGetApplicationBasicDataErr(): void
    {
        $this->mockStatisticHandlerException('getApplicationsBasicData');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/application500Request.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::getApplicationsUsersAction
     * @covers \Hanaboso\Applinth\Handler\StatisticsHandler::getApplicationsUsers
     * @covers \Hanaboso\Applinth\Manager\StatisticsManager::getApplicationsUsers
     *
     * @throws Exception
     */
    public function testGetApplicationsUsers(): void
    {
        $this->createApps();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/applicationHubspotRequest.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::getApplicationsUsersAction
     *
     * @throws Exception
     */
    public function testGetApplicationUserErr(): void
    {
        $this->mockStatisticHandlerException('getApplicationsUsers');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/applicationHubspot500Request.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::applicationStatisticsAction
     *
     * @throws Exception
     */
    public function testApplicationStatisticsAction(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/applicationStatisticsRequest.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::applicationStatisticsAction
     *
     * @throws Exception
     */
    public function testApplicationStatisticsActionErr(): void
    {
        $this->mockStatisticHandlerException('getApplicationMetrics');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/applicationStatistics400Request.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::userStatisticsAction
     *
     * @throws Exception
     */
    public function testUserStatisticsAction(): void
    {
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/userStatisticsRequest.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::userStatisticsAction
     *
     * @throws Exception
     */
    public function testUserStatisticsActionErr(): void
    {
        $this->mockStatisticHandlerException('getUserMetrics');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/userStatistics400Request.json',
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::getUsersBasicDataAction
     * @covers \Hanaboso\Applinth\Handler\StatisticsHandler::getUsersBasicData
     * @covers \Hanaboso\Applinth\Manager\StatisticsManager::getUsersBasicData
     *
     * @throws Exception
     */
    public function testGetUsersBasicData(): void
    {
        $this->createApps();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/usersRequest.json',
            ['id' => '123456'],
        );
    }

    /**
     * @covers \Hanaboso\Applinth\Controller\StatisticsController::getUsersBasicDataAction
     *
     * @throws Exception
     */
    public function testGetUsersBasicDataErr(): void
    {
        $this->mockStatisticHandlerException('getUsersBasicData');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/StatisticsController/users500Request.json',
        );
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
        self::getContainer()->set('hbpf._application.handler.statistics', $mock);
    }

}
