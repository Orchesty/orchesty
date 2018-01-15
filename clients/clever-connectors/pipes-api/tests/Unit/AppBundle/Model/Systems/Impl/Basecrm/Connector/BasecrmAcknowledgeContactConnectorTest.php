<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmAcknowledgeContactConnector;
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
 * Class BasecrmAcknowledgeContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmAcknowledgeContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $conn = new BasecrmAcknowledgeContactConnector(
            $this->mockCurl(),
            $this->container->get('systems.basecrm'),
            $this->mockDm()
        );

        $dto = new ProcessDto();
        $dto->setData($this->getRequest('contactItem.json'))
            ->setHeaders([

            ]);
        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testLimit(): void
    {
        $conn = new BasecrmAcknowledgeContactConnector(
            $this->mockCurl(429),
            $this->container->get('systems.basecrm'),
            $this->mockDm()
        );

        $dto = new ProcessDto();
        $dto->setData($this->getRequest('contactItem.json'))
            ->setHeaders([

            ]);
        $res = $conn->processAction($dto);
        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testUnexpectedError(): void
    {
        $conn = new BasecrmAcknowledgeContactConnector(
            $this->mockCurl(404),
            $this->container->get('systems.basecrm'),
            $this->mockDm()
        );

        $conn->setLogger($this->mockLogger());

        $dto = new ProcessDto();
        $dto->setData($this->getRequest('contactItem.json'))
            ->setHeaders([

            ]);

        $this->expectException(CurlException::class);
        $conn->processAction($dto);
    }

    /**
     * @param int $status
     *
     * @return CurlManagerInterface
     */
    private function mockCurl(int $status = 200): CurlManagerInterface
    {
        $_SERVER['HTTP_USER_AGENT'] = 'asd';
        $test                       = $this;

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);

        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto) use ($test, $status) {
                    $expt = new RequestDto('POST', new Uri('https://api.getbase.com/v2/sync/ack'));
                    $expt->setBody(json_encode([
                        'data' => [
                            'ack_keys' => [
                                'create|Contact|187596661|1508401118|16257613280',
                            ],
                        ],
                    ]))
                        ->setHeaders([
                            'Accept'                => 'application/json',
                            'Content-Type'          => 'application/json',
                            'User-Agent'            => 'Chrome/58.0.3029.96 Safari/537.36',
                            'Authorization'         => 'Bearer sdgfd6g465g46f456f',
                            'X-Basecrm-Device-UUID' => 'asdgdf546s45gfs6',
                        ]);

                    $test->assertEquals($expt, $dto);

                    if ($status >= 300) {
                        throw new CurlException('', $status, NULL, new Response($status));
                    }

                    return new ResponseDto($status, '', '', []);
                }
            ));

        return $curl;
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm()
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setUser('user')
            ->setExpires(NULL)
            ->setSystem('system')
            ->setToken('token')
            ->setSettings([
                'access_token' => 'sdgfd6g465g46f456f',
                'sync_uuid'    => 'asdgdf546s45gfs6',
                'que_id'       => 'fgh54h5mzf',
            ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

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
            ->method('info')->will($this->returnCallback(
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