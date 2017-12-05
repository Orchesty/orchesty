<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMGetListSubscribersConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class CMGetListSubscribersConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SubscriberConnector
 */
final class CMGetListSubscribersConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers CMGetListSubscribersConnector::processBatch()
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();
        $dto  = (new ProcessDto())->setHeaders([
            'pf-guid'       => '51a83cfe-9e04-11e7-a177-000d3a20eb16',
            'pf-token'      => '-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k',
            'pf-system-key' => 'neco',
        ]);

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('51a83cfe-9e04-11e7-a177-000d3a20eb16')
            ->setToken('-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k')
            ->setSystem('neco');

        $repository = $this->createMock(SystemInstallRepository::class);
        $repository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($repository);

        $curlSender = $this->createMock(CurlSender::class);

        /** @var MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->expects($this->at(0))
            ->method('create')
            ->willReturn($curlSender);

        /** @var MockObject|CMGetListSubscribersConnector $conn */
        $conn = $this->getMockBuilder(CMGetListSubscribersConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$dm, $factory, ['ca' => '', 'cert' => '']])
            ->getMock();

        $conn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode([['email' => 'aa@aa.com']]))));

        $conn->expects($this->at(1))
            ->method('fetchData')
            ->willReturn(resolve(new Response(204, [], json_encode([['email' => 'aa@aa.com']]))));

        $process = $conn->processBatch($dto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        });

        $process->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function ($data): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

}