<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskCreateUserConnector;
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
 * Class ZendeskCreateUserConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskCreateUserConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var string
     */
    private $auth;

    /**
     *
     */
    public function testConnector(): void
    {
        $this->auth = base64_encode('eml@eml.com/token:smToken');
        $conn       = new ZendeskCreateUserConnector(
            $this->container->get('systems.zendesk'),
            $this->mockDm(),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            'user' => [
                'email' => 'eml@eml.com',
                'name'  => 'first last',
            ],
        ]));

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testConnectorLimit(): void
    {
        $this->auth = base64_encode('eml@eml.com/token:smToken');
        $conn       = new ZendeskCreateUserConnector(
            $this->container->get('systems.zendesk'),
            $this->mockDm(),
            $this->mockCurl(429)
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
        $this->auth = base64_encode('eml@eml.com/token:smToken');
        $conn       = new ZendeskCreateUserConnector(
            $this->container->get('systems.zendesk'),
            $this->mockDm(),
            $this->mockCurl(400)
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData('');

        $conn->setLogger($this->mockLogger());
        $this->expectException(CurlException::class);
        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'user_email' => 'eml@eml.com',
            'api_token'  => 'smToken',
            'domain'     => 'hbpf',
        ])->setUser('gguid')->setToken('tkn');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
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
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) use ($status) {
                if ($status >= 300) {
                    throw new CurlException('', 0, NULL, new Response($status));
                }

                $expt = new RequestDto('POST', new Uri('https://hbpf.zendesk.com/api/v2/users.json'));
                $expt->setHeaders([
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Basic ' . $this->auth,
                ])->setBody(json_encode([
                    'user' => [
                        'email' => 'eml@eml.com',
                        'name'  => 'first last',
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto($status, '', '', []);
            }));

        return $curl;
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
                        'guid'              => 'gguid',
                        'token'             => 'tkn',
                        'system_key'        => 'zendesk',
                        'system_name'       => 'Zendesk',
                    ], $data);
                }
            ));

        return $logger;
    }

}