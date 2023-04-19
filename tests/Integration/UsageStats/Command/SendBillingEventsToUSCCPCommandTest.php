<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\UsageStats\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\UsageStats\Document\BillingData;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
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
     * @covers \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Command\SendUsageStatsEventsToUSCCPCommand::sendRequest
     * @covers \Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository::findBillingEvents
     * @covers \Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository::getRemainingEventCount
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

        $data = $this->dm->getRepository(UsageStatsEvent::class)->findAll();

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

        return [$resEx, $res, $res, $res, $res];
    }

    /**
     * @param DocumentManager $dm
     *
     * @return void
     * @throws MongoDBException
     */
    private function mockTestData(DocumentManager $dm): void
    {
        $usageStatEvent1 = (new UsageStatsEvent('1234', EventTypeEnum::INSTALL->value))->setBillingData(
            new BillingData('1', '1'),
        );
        $usageStatEvent2 = (new UsageStatsEvent('1235', EventTypeEnum::UNINSTALL->value))->setBillingData(
            new BillingData('2', '2'),
        );
        $dm->persist($usageStatEvent1);
        $dm->persist($usageStatEvent2);
        $dm->flush();
    }

}
