<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\Applinth\Controller\StatisticsController;
use Hanaboso\Applinth\Handler\StatisticsHandler;
use Hanaboso\Applinth\Manager\StatisticsManager;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class StatisticsControllerTest
 *
 * @package ApplinthTests\Controller
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(StatisticsController::class)]
#[CoversClass(StatisticsHandler::class)]
#[CoversClass(StatisticsManager::class)]
final class StatisticsControllerTest extends ControllerTestCaseAbstract
{

    /**
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

    /*
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
        $mock->expects(self::atLeastOnce())->method($fn)->willThrowException(new MongoDBException());
        self::getContainer()->set('hbpf._application.handler.statistics', $mock);
    }

}
