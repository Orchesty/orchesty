<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoUpdateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => '76762000000080046',
            ]))->setHeaders([
                CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
            ])
        )->getData(), TRUE);

        $this->assertEquals([
            'response' => [
                'result' => [
                    'recorddetail' => [
                        'FL' => [
                            0 => [
                                'val'     => 'Id',
                                'content' => '76762000000080046',
                            ],
                            1 => [
                                'val'     => 'Created Time',
                                'content' => '2017-10-30 16:44:26',
                            ],
                            2 => [
                                'val'     => 'Modified Time',
                                'content' => '2017-10-31 10:38:46',
                            ],
                            3 => [
                                'val'     => 'Created By',
                                'content' => 'Radek M',
                            ],
                            4 => [
                                'val'     => 'Modified By',
                                'content' => 'Radek M',
                            ],
                        ],
                    ],
                    'message'      => 'Record(s) updated successfully',
                ],
                'uri'    => '/crm/private/json/Contacts/updateRecords',
            ],
        ], $result);
    }

    /**
     *
     */
    public function testConnectorLimit(): void
    {
        $res = $this->getConnectorMock(4820)->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => '76762000000080046',
            ]))->setHeaders([
                CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
            ])
        );

        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

    /**
     *
     */
    public function testConnectorError(): void
    {
        $conn = $this->getConnectorMock(4001);
        $conn->setLogger($this->mockLogger());
        $this->expectException(CleverConnectorsException::class);

        $conn->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => '76762000000080046',
            ]))->setHeaders([
                CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
            ])
        );

    }

    /**
     * @param int $status
     *
     * @return ZohoUpdateContactConnector
     */
    private function getConnectorMock(int $status = 200): ZohoUpdateContactConnector
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')->setToken('tkn')
            ->setSettings(['auth_token' => '05361930f1c8c009d9a1e30e07b23126']);

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $systemInstall */
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) use ($status) {
                if ($status >= 300) {
                    return new ResponseDto(200, '', json_encode([
                        'response' => [
                            'error' => [
                                'code'    => (string) $status,
                                'message' => 'error_message',
                            ],
                        ],
                    ]), []);
                }

                $this->assertEquals(
                    new Uri('https://crm.zoho.eu/crm/private/json/Contacts/updateRecords?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi&id=76762000000080046&newFormat=1&xmlData=<Contacts><row no="1"><FL val="cm_unsubscribe">1</FL></row></Contacts>'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('updateContact.json'), []);
            }));
        $system = $this->container->get('systems.zoho');

        return new ZohoUpdateContactConnector($system, $documentManager, $curlManager);
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
                    self::assertEquals('access_expiration', $type);
                    self::assertEquals([
                        'notification_type' => 'access_expiration',
                        'guid'              => 'usr',
                        'token'             => 'tkn',
                        'system_key'        => 'zoho',
                        'system_name'       => 'ZOHO',
                    ], $data);
                }
            ));

        return $logger;
    }

}