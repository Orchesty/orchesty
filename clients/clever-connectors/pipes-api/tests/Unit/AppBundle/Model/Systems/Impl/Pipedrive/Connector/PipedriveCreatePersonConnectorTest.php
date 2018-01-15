<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector\PipedriveCreatePersonConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class PipedriveCreatePersonConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveCreatePersonConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $conn = new PipedriveCreatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(),
            $this->mockDm()
        );

        $data = [
            'id'   => 'pid',
            'body' => json_encode([
                'name'  => 'sore namae',
                'email' => 'eml@eml.com',
            ]),
        ];

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorLimit(): void
    {
        $conn = new PipedriveCreatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(429),
            $this->mockDm()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData('');

        $res = $conn->processAction($dto);

        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testConnectorError(): void
    {
        $conn = new PipedriveCreatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(404),
            $this->mockDm()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData('');

        $conn->setLogger($this->mockLogger());
        $this->expectException(CurlException::class);
        $conn->processAction($dto);
    }

    /**
     * @param int $status
     *
     * @return CurlManagerInterface
     */
    private function mockCurl(int $status = 201): CurlManagerInterface
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->at(0))
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) use ($status) {
                if ($status >= 300) {
                    throw new CurlException('', 0, NULL, new Response($status));
                }

                $expt = new RequestDto('POST', new Uri('https://api.pipedrive.com/v1/persons?api_token=token'));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->setBody(json_encode([
                    'id'   => 'pid',
                    'body' => json_encode([
                        'name'  => 'sore namae',
                        'email' => 'eml@eml.com',
                    ]),
                ]));

                self::assertEquals($expt, $dto);

                return new ResponseDto($status, '', '', []);
            }));

        return $curl;
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm()
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            CleverCustomKeysEnum::HARD_BOUNCE => 'hardhash',
            'api_token'                       => 'token',
        ])->setUser('usr')->setToken('tkn');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return LoggerInterface
     */
    private function mockLogger(): LoggerInterface
    {
        /** @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')->will($this->returnCallback(
                function (string $type, $data): void {
                    self::assertEquals('data_error', $type);
                    self::assertEquals([
                        'notification_type' => 'data_error',
                        'guid'              => 'usr',
                        'token'             => 'tkn',
                        'system_key'        => 'pipedrive',
                        'system_name'       => 'Pipedrive',
                    ], $data);
                }
            ));

        return $logger;
    }

}