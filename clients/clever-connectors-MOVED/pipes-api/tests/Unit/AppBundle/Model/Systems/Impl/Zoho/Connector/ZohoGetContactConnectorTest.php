<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoGetContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoGetContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoGetContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode(['id' => '76762000000078126']))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'no' => '1',
            'FL' => [
                0  => [
                    'val'     => 'CONTACTID',
                    'content' => '76762000000075653',
                ],
                1  => [
                    'val'     => 'SMOWNERID',
                    'content' => '76762000000075011',
                ],
                2  => [
                    'val'     => 'Contact Owner',
                    'content' => 'Radek M',
                ],
                3  => [
                    'val'     => 'First Name',
                    'content' => 'a',
                ],
                4  => [
                    'val'     => 'Last Name',
                    'content' => 'a',
                ],
                5  => [
                    'val'     => 'Full Name',
                    'content' => 'a a',
                ],
                6  => [
                    'val'     => 'Email',
                    'content' => 'a@a.com',
                ],
                7  => [
                    'val'     => 'Email Opt Out',
                    'content' => 'false',
                ],
                8  => [
                    'val'     => 'SMCREATORID',
                    'content' => '76762000000075011',
                ],
                9  => [
                    'val'     => 'Created By',
                    'content' => 'Radek M',
                ],
                10 => [
                    'val'     => 'MODIFIEDBY',
                    'content' => '76762000000075011',
                ],
                11 => [
                    'val'     => 'Modified By',
                    'content' => 'Radek M',
                ],
                12 => [
                    'val'     => 'Created Time',
                    'content' => '2017-10-31 14:27:38',
                ],
                13 => [
                    'val'     => 'Modified Time',
                    'content' => '2017-10-31 14:27:38',
                ],
                14 => [
                    'val'     => 'Last Activity Time',
                    'content' => '2017-10-31 14:27:38',
                ],
            ],
        ], $result);
    }

    /**
     *
     */
    public function testConnectorLimit(): void
    {
        $conn = $this->getConnectorMock(4820);

        $result = $conn->processAction(
            (new ProcessDto())->setData('{"id":"id"}')->setHeaders([])
        );

        self::assertArrayHasKey('pf-result-code', $result->getHeaders());
        self::assertEquals(1004, $result->getHeaders()['pf-result-code']);
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
            (new ProcessDto())->setData('{"id":"id"}')->setHeaders([])
        );
    }

    /**
     * @param int $status
     *
     * @return ZohoGetContactConnector
     */
    private function getConnectorMock(int $status = 200): ZohoGetContactConnector
    {
        $sys = new SystemInstall();
        $sys->setSettings(['auth_token' => '05361930f1c8c009d9a1e30e07b23126'])
            ->setToken('tkn')->setUser('usr');

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $systemInstall */
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $documentManager */
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
                    new Uri('https://crm.zoho.eu/crm/private/json/Contacts/getRecordById?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi&id=76762000000078126'),
                    $dto->getUri()
                );

                return new ResponseDto($status, 'OK', $this->getRequest('getSingleContact.json'), []);
            }));
        $system = $this->ownContainer->get('systems.zoho');

        return new ZohoGetContactConnector($system, $documentManager, $curlManager);
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