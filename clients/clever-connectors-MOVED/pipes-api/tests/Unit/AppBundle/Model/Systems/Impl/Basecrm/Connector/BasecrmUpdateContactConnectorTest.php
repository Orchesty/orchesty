<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmUpdateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testUpdateConnector(): void
    {
        $data = json_encode([
            'data' => [
                'custom_fields' => [
                    CleverCustomKeysEnum::HARD_BOUNCE => FALSE,
                ],
            ],
        ]);

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ])->setData(json_encode([
            'id'   => 'someId',
            'body' => $data,
        ]));

        $conn = new BasecrmUpdateContactConnector(
            $this->ownContainer->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl($data)
        );

        $conn->processAction($dto);
    }

    /**
     *
     */
    public function testLimit(): void
    {
        $data = json_encode([
            'data' => [
                'custom_fields' => [
                    CleverCustomKeysEnum::HARD_BOUNCE => FALSE,
                ],
            ],
        ]);

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ])->setData(json_encode([
            'id'   => 'someId',
            'body' => $data,
        ]));

        $conn = new BasecrmUpdateContactConnector(
            $this->ownContainer->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl($data, 429)
        );

        $res = $conn->processAction($dto);
        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testUnexpectedError(): void
    {
        $data = json_encode([
            'data' => [
                'custom_fields' => [
                    CleverCustomKeysEnum::HARD_BOUNCE => FALSE,
                ],
            ],
        ]);

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ])->setData(json_encode([
            'id'   => 'someId',
            'body' => $data,
        ]));

        $conn = new BasecrmUpdateContactConnector(
            $this->ownContainer->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl($data, 404)
        );

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
            'access_token' => 'someToken',
        ])->setUser('asd')->setToken('qew');

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
     * @param string $data
     * @param int    $status
     *
     * @return CurlManagerInterface
     */
    private function mockCurl(string $data = '', $status = 200): CurlManagerInterface
    {
        $test = $this;

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) use ($test, $data, $status) {
                $expt = new RequestDto('PUT', new Uri('https://api.getbase.com/v2/contacts/someId'));
                $expt->setBody($data)
                    ->setHeaders([
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                        'User-Agent'    => 'Chrome/58.0.3029.96 Safari/537.36',
                        'Authorization' => 'Bearer someToken',
                    ]);
                $test->assertEquals($expt, $requestDto);
                if ($status >= 300) {
                    throw new CurlException('', $status, NULL, new Response($status));
                }

                return new ResponseDto($status, '', $this->getRequest('contactCreated.json'), []);
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
            ->method('error')->will($this->returnCallback(
                function (string $type, $data): void {

                    $this->assertEquals(NotificationTypeEnum::DATA_ERROR, $type);
                    $this->assertEquals([
                        'notification_type' => 'data_error',
                        'guid'              => 'asd',
                        'token'             => 'qew',
                        'system_key'        => 'basecrm',
                        'system_name'       => 'BaseCRM',
                    ], $data);
                }
            ));

        return $logger;
    }

}