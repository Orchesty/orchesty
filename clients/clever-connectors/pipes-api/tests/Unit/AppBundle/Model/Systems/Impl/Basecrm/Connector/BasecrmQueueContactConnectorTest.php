<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmQueueContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
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
 * Class BasecrmQueueContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmQueueContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $systemInstall = [
            'user'              => 'user',
            'token'             => 'token',
            'system'            => 'system',
            'synchronised'      => FALSE,
            'encryptedSettings' => CryptManager::encrypt([
                'access_token' => 'hn6465gfb',
            ]),
        ];

        $conn = $this->mockResponses();

        $dtoData = [
            'system_install' => $systemInstall,
            'topology'       => ['name' => 'topology'],
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $conn->processAction($processDto);
    }

    /**
     *
     */
    public function testLimit(): void
    {
        $systemInstall = [
            'user'              => 'user',
            'token'             => 'token',
            'system'            => 'system',
            'synchronised'      => FALSE,
            'encryptedSettings' => CryptManager::encrypt([
                'access_token' => 'hn6465gfb',
            ]),
        ];

        $conn = $this->mockResponses(429);

        $dtoData = [
            'system_install' => $systemInstall,
            'topology'       => ['name' => 'topology'],
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $res = $conn->processAction($processDto);
        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testUnexpectedError(): void
    {
        $systemInstall = [
            'user'              => 'user',
            'token'             => 'token',
            'system'            => 'system',
            'synchronised'      => FALSE,
            'encryptedSettings' => CryptManager::encrypt([
                'access_token' => 'hn6465gfb',
            ]),
        ];

        $conn = $this->mockResponses(404);

        $dtoData = [
            'system_install' => $systemInstall,
            'topology'       => ['name' => 'topology'],
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $conn->setLogger($this->mockLogger());
        $this->expectException(CurlException::class);
        $conn->processAction($processDto);
    }

    /**
     * @param int $status
     *
     * @return BasecrmQueueContactConnector
     */
    private function mockResponses(int $status = 201): BasecrmQueueContactConnector
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->getMockBuilder(CurlManagerInterface::class)->disableOriginalConstructor()
            ->setMethods(['send'])->getMock();

        $test = $this;

        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto) use ($test, $status) {
                    $expt = new RequestDto('POST',
                        new Uri('https://api.getbase.com/v2/sync/start'));
                    $expt->setHeaders([
                        'Accept'                => 'application/json',
                        'Content-Type'          => 'application/json',
                        'User-Agent'            => 'Chrome/58.0.3029.96 Safari/537.36',
                        'Authorization'         => 'Bearer hn6465gfb',
                        'X-Basecrm-Device-UUID' => $dto->getHeaders()['X-Basecrm-Device-UUID'],
                    ]);

                    $test->assertEquals($expt, $dto);

                    if ($status >= 300) {
                        throw new CurlException('', $status, NULL, new Response($status));
                    }

                    return new ResponseDto($status, '', $this->getRequest('syncStartResponse.json'),
                        $expt->getHeaders());
                }
            ));

        return new BasecrmQueueContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDM(),
            $curl
        );
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDM(): DocumentManager
    {
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('saveSystemInstall')->willReturn([]);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

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

                    $this->assertEquals(NotificationTypeEnum::DATA_ERROR, $type);
                    $this->assertEquals([
                        'notification_type' => 'data_error',
                        'guid'              => 'user',
                        'token'             => 'token',
                        'system_key'        => 'basecrm',
                        'system_name'       => 'BaseCRM',
                    ], $data);
                }
            ));

        return $logger;
    }

}