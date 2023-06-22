<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\UsageStats\Command;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData;
use Hanaboso\PipesFramework\UsageStats\Document\OperationBillingData;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\Utils\Exception\DateTimeException;
use PHPUnit\Framework\MockObject\Exception;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class SendBillingEventsToUSCCPCommandTest
 *
 * @package PipesFrameworkTests\Integration\UsageStats\Command
 */
final class SendBillingEventsToUSCCPCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Command\SendUsageStatsEventsToUSCCPCommand::execute
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Command\SendUsageStatsEventsToUSCCPCommand::configure
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\SenderAbstract::sendRequest
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\SenderAbstract::send
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\SenderManager::registerSender
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\SenderManager::send
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\OperationUsageStatsSender::generateOperationEvents
     * @covers \Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository::findBillingEventsByTypesForSender
     * @covers \Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository::getRemainingEventCount
     * @covers \Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository::getDataForOperationEventSending
     *
     * @return void
     * @throws DateTimeException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testExecute(): void
    {
        $methods = $this->prepareMethods();

        $this->mockTestData($this->dm);

        $curl = $this->createMock(CurlManager::class);
        $curl
            ->method('send')
            ->willReturnCallback(static function (RequestDto $request) use ($methods) {
                static $counter = 0;

                return $methods[$counter++]($request);
            });

        $application = new Application(self::$kernel);
        $command     = $application->get('usage_stats:send-events');
        $this->setProperty($command, 'curlManager', $curl);
        $commandTester = new CommandTester($command);
        $result        = $commandTester->execute(['command' => $command->getName()]);

        self::assertEquals(0, $result);

        $data = $this->dm->getRepository(UsageStatsEvent::class)->findByTypes(
            [EventTypeEnum::INSTALL->value, EventTypeEnum::UNINSTALL->value],
        );

        self::assertEquals(
            [
                'created' => $data[0]->getCreated()->format('Uu'),
                'data'    => [
                    'aid'  => '1',
                    'euid' => '1',
                ],
                'iid'     => '1234',
                'type'    => 'applinth_enduser_app_install',
                'version' => 1,
            ],
            $data[0]->toArray(),
        );
        self::assertNotEquals(NULL, $data[0]->getSent());
        self::assertEquals(
            [
                'created' => $data[1]->getCreated()->format('Uu'),
                'data'    => [
                    'aid'  => '2',
                    'euid' => '2',
                ],
                'iid'     => '1235',
                'type'    => 'applinth_enduser_app_uninstall',
                'version' => 1,
            ],
            $data[1]->toArray(),
        );
        self::assertNotEquals(NULL, $data[1]->getSent());

        $data = $this->dm->getRepository(UsageStatsEvent::class)->findByTypes(
            [EventTypeEnum::OPERATION->value],
        );

        usort($data, static function ($a, $b) {
            if ($a->getData()['total'] > $b->getData()['total']) {
                return 1;
            }
            if ($a->getData()['total'] < $b->getData()['total']) {
                return -1;
            }

            return 0;
        });

        self::assertEquals(
            [
                [
                    'created' => $data[0]->getCreated()->format('Uu'),
                    'data'    => [
                        'day'   => '2023-01-01',
                        'total' => 1,
                    ],
                    'iid'     => 'orchesty',
                    'type'    => 'orchesty_operation',
                    'version' => 1,
                ],
                [
                    'created' => $data[1]->getCreated()->format('Uu'),
                    'data'    => [
                        'day'   => '2022-01-03',
                        'total' => 8,
                    ],
                    'iid'     => 'orchesty',
                    'type'    => 'orchesty_operation',
                    'version' => 1,
                ],
                [
                    'created' => $data[2]->getCreated()->format('Uu'),
                    'data'    => [
                        'day'   => '2023-01-03',
                        'total' => 23,
                    ],
                    'iid'     => '1234',
                    'type'    => 'orchesty_operation',
                    'version' => 1,
                ],
                [
                    'created' => $data[3]->getCreated()->format('Uu'),
                    'data'    => [
                        'day'   => '2023-01-03',
                        'total' => 31,
                    ],
                    'iid'     => 'orchesty',
                    'type'    => 'orchesty_operation',
                    'version' => 1,
                ],
            ],
            [$data[0]->toArray(), $data[1]->toArray(), $data[2]->toArray(), $data[3]->toArray()],
        );
        self::assertNotEquals(NULL, $data[0]->getSent());
        self::assertNotEquals(NULL, $data[1]->getSent());
        self::assertNotEquals(NULL, $data[2]->getSent());
        self::assertNotEquals(NULL, $data[3]->getSent());
    }

    /**
     * @return mixed[]
     */
    private function prepareMethods(): array
    {
        $resEx = static function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
            self::assertEquals('https://usccp.cloud.orchesty.io', $request->getUri(TRUE));

            throw new CurlException();
        };
        $res   = static function (RequestDto $request): ResponseDto {
            self::assertEquals(CurlManager::METHOD_PUT, $request->getMethod());
            self::assertEquals('https://usccp.cloud.orchesty.io', $request->getUri(TRUE));

            return new ResponseDto(200, 'OK', '[{}]', []);
        };

        return [$resEx, $res, $res, $res, $res, $res, $res, $res, $res, $res];
    }

    /**
     * @param DocumentManager $dm
     *
     * @return void
     * @throws DateTimeException
     * @throws MongoDBException
     */
    private function mockTestData(DocumentManager $dm): void
    {
        $usageStatEvent1 = (new UsageStatsEvent('1234', EventTypeEnum::INSTALL->value))->setAppInstallBillingData(
            new AppInstallBillingData('1', '1'),
        );
        $usageStatEvent2 = (new UsageStatsEvent('1235', EventTypeEnum::UNINSTALL->value))->setAppInstallBillingData(
            new AppInstallBillingData('2', '2'),
        );

        $multiCounterData  = (new TopologyProgress())
            ->setTopologyId('1')
            ->setStartedAt(new DateTime('2023-01-01'))
            ->setFinishedAt(new DateTime('2023-01-01'))
            ->setProcessedCount(1)
            ->setNok(1);
        $multiCounterData2 = (new TopologyProgress())
            ->setTopologyId('2')
            ->setStartedAt(new DateTime('2023-01-03'))
            ->setFinishedAt(new DateTime('2023-01-03'))
            ->setProcessedCount(50)
            ->setNok(0);
        $multiCounterData3 = (new TopologyProgress())
            ->setTopologyId('2')
            ->setStartedAt(new DateTime('2022-01-03'))
            ->setFinishedAt(new DateTime('2022-01-03'))
            ->setProcessedCount(3)
            ->setNok(3);
        $multiCounterData4 = (new TopologyProgress())
            ->setTopologyId('2')
            ->setStartedAt(new DateTime('2023-01-03'))
            ->setFinishedAt(new DateTime('2023-01-03'))
            ->setProcessedCount(4)
            ->setNok(10);
        $multiCounterData5 = (new TopologyProgress())
            ->setTopologyId('2')
            ->setStartedAt(new DateTime('2022-01-03'))
            ->setFinishedAt(new DateTime('2023-01-03'))
            ->setProcessedCount(5)
            ->setNok(3);
        $usageStatsEvent   = (new UsageStatsEvent('1234', EventTypeEnum::OPERATION->value))
            ->setCreated(new DateTime('2023-01-01'))
            ->setOperationBillingData(new OperationBillingData('2023-01-03', 23))
            ->setSent(1_687_261_631)
            ->setVersion(1);
        $dm->persist($multiCounterData);
        $dm->persist($multiCounterData2);
        $dm->persist($multiCounterData3);
        $dm->persist($multiCounterData4);
        $dm->persist($multiCounterData5);
        $dm->persist($usageStatsEvent);
        $dm->persist($usageStatEvent1);
        $dm->persist($usageStatEvent2);
        $dm->flush();
    }

}
