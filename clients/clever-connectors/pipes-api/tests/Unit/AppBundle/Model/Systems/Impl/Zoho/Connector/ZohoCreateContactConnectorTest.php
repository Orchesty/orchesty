<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoCreateContactConnector;
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
 * Class ZohoCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode([
                'xml' => '<Contacts><row no="1"><FL val="Email">email@example.com</FL><FL val="First Name">First Name</FL><FL val="Last Name">Last Name</FL></row></Contacts>',
            ]))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals(['id' => '76762000000078126'], $result);
    }

    /**
     *
     */
    public function testConnectorLimit(): void
    {
        $result = $this->getConnectorMock(4820)->processAction(
            (new ProcessDto())->setData(Json::encode([
                'xml' => '<Contacts><row no="1"><FL val="Email">email@example.com</FL><FL val="First Name">First Name</FL><FL val="Last Name">Last Name</FL></row></Contacts>',
            ]))->setHeaders([])
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
            (new ProcessDto())->setData(Json::encode([
                'xml' => '<Contacts><row no="1"><FL val="Email">email@example.com</FL><FL val="First Name">First Name</FL><FL val="Last Name">Last Name</FL></row></Contacts>',
            ]))->setHeaders([])
        );
    }

    /**
     * @param int $status
     *
     * @return ZohoCreateContactConnector
     */
    private function getConnectorMock(int $status = 200): ZohoCreateContactConnector
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')->setToken('tkn')
            ->setSettings(['auth_token' => '05361930f1c8c009d9a1e30e07b23126']);

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $systemInstall */
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $documentManager */
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
                    new Uri('https://crm.zoho.eu/crm/private/json/Contacts/insertRecords?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi&newFormat=1&xmlData=%3CContacts%3E%3Crow%20no=%221%22%3E%3CFL%20val=%22Email%22%3Eemail@example.com%3C/FL%3E%3CFL%20val=%22First%20Name%22%3EFirst%20Name%3C/FL%3E%3CFL%20val=%22Last%20Name%22%3ELast%20Name%3C/FL%3E%3C/row%3E%3C/Contacts%3E'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('createContact.json'), []);
            }));

        $system = $this->container->get('systems.zoho');

        return new ZohoCreateContactConnector($system, $documentManager, $curlManager);
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