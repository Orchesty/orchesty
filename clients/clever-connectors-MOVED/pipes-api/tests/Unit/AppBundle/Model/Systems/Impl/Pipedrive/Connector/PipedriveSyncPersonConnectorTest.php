<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector\PipedriveSyncPersonConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class PipedriveSyncPersonConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveSyncPersonConnectorTest extends ConnectorTestCaseAbstract
{

    private const API_TOKEN = 'sdgfd6g465g46f456f';

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $conn = $this->mockSync();

        $dtoData = [
            'system_install' => ['user' => 'user'],
            'topology'       => ['name' => 'topology'],
        ];

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $data = $conn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();

    }

    /**
     * @return MockObject|PipedriveSyncPersonConnector
     */
    private function mockSync(): PipedriveSyncPersonConnector
    {
        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $conn = $this->getMockBuilder(PipedriveSyncPersonConnector::class)->setConstructorArgs([
            $this->ownContainer->get('systems.pipedrive'),
            $this->mockDm(),
            $this->createMock(CurlSenderFactory::class),
            $processCounter,
        ])->setMethods(['fetchData'])->getMock();

        $test = $this;

        $conn->expects($this->at(0))
            ->method('fetchData')->will($this->returnCallback(
                function ($sender, RequestDto $dto) use ($test) {
                    $expt = new RequestDto('GET',
                        new Uri('https://api.pipedrive.com/v1/persons?start=0&limit=50&api_token=' . self::API_TOKEN));
                    $expt->setHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ]);

                    $test->assertEquals($expt, $dto);

                    return resolve(new Response(200, $expt->getHeaders(), $this->getRequest('personsPage.json')));
                }
            ));

        return $conn;
    }

    /**
     * @return MockObject|DocumentManager
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setToken('token')
            ->setUser('user')
            ->setSystem('system')
            ->setSynchronized(FALSE)
            ->setSettings([
                'api_token' => self::API_TOKEN,
            ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

}