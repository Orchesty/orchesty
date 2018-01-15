<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmCreateContactConnector;
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
 * Class BasecrmCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testCreateContact(): void
    {
        $data = [
            'data' => [
                'first_name'    => 'first',
                'last_name'     => 'last',
                'email'         => 'eml@eml.com',
                'custom_fields' => [
                    'cm_hard_bounce' => FALSE,
                    'cm_unsubscribe' => FALSE,
                ],
            ],
        ];

        $conn = new BasecrmCreateContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl($data)
        );
        $dto  = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testLimit(): void
    {
        $conn = new BasecrmCreateContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl([], 429)
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
        $conn = new BasecrmCreateContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl([], 404)
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
     * @param array $data
     * @param int   $status
     *
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(array $data, int $status = 200): CurlManagerInterface
    {
        $test = $this;
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) use ($test, $status, $data) {
                if ($status >= 300) {
                    throw new CurlException('', $status, NULL, new Response($status));
                }

                $expt = new RequestDto('POST', new Uri('https://api.getbase.com/v2/contacts'));
                $expt->setHeaders([
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'User-Agent'    => 'Chrome/58.0.3029.96 Safari/537.36',
                    'Authorization' => 'Bearer acctoken',
                ])->setBody(json_encode($data));

                $test->assertEquals($expt, $dto);

                return new ResponseDto($status, '', '', []);
            }));

        return $curl;
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings(['access_token' => 'acctoken'])
            ->setUser('user')
            ->setToken('tkn');

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
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

                    $this->assertEquals(NotificationTypeEnum::DATA_ERROR, $type);
                    $this->assertEquals([
                        'notification_type' => 'data_error',
                        'guid'              => 'user',
                        'token'             => 'tkn',
                        'system_key'        => 'basecrm',
                        'system_name'       => 'BaseCRM',
                    ], $data);
                }
            ));

        return $logger;
    }

}