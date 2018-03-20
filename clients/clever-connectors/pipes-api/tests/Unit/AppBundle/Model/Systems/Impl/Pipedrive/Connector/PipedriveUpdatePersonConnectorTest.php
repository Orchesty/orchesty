<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector\PipedriveUpdatePersonConnector;
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
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveUpdatePersonConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveUpdatePersonConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var DocumentManager
     */
    private $dmMock;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dmMock = $this->mockDm();
    }

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $conn = new PipedriveUpdatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(),
            $this->dmMock
        );

        $data = [
            'id'   => 'pid',
            'body' => json_encode([
                'hardhash' => TRUE,
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
        $conn = new PipedriveUpdatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(429),
            $this->dmMock
        );

        $data = [
            'id'   => 'pid',
            'body' => json_encode([
                'hardhash' => TRUE,
            ]),
        ];

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));
        $res = $conn->processAction($dto);

        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testConnectorError(): void
    {
        $conn = new PipedriveUpdatePersonConnector(
            $this->container->get('systems.pipedrive'),
            $this->mockCurl(400),
            $this->dmMock
        );

        $data = [
            'id'   => 'pid',
            'body' => json_encode([
                'hardhash' => TRUE,
            ]),
        ];

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));

        $conn->setLogger($this->mockLogger());
        $this->expectException(CurlException::class);
        $conn->processAction($dto);
    }

    /**
     * @param int $status
     *
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(int $status = 200): CurlManagerInterface
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->at(0))
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) use ($status) {
                if ($status >= 300) {
                    throw new CurlException('', 0, NULL, new Response($status));
                }

                $expt = new RequestDto('PUT', new Uri('https://api.pipedrive.com/v1/persons/pid?api_token=token'));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->setBody(json_encode([
                    'hardhash' => TRUE,
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
            ->method('error')->will($this->returnCallback(
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