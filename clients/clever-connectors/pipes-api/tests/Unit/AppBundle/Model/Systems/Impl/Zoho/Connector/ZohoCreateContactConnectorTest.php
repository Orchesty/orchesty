<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoCreateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
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
     * @return ZohoCreateContactConnector
     */
    private function getConnectorMock(): ZohoCreateContactConnector
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                $this->assertEquals(
                    new Uri('https://crm.zoho.eu/crm/private/json/Contacts/insertRecords?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi&newFormat=1&xmlData=%3CContacts%3E%3Crow%20no=%221%22%3E%3CFL%20val=%22Email%22%3Eemail@example.com%3C/FL%3E%3CFL%20val=%22First%20Name%22%3EFirst%20Name%3C/FL%3E%3CFL%20val=%22Last%20Name%22%3ELast%20Name%3C/FL%3E%3C/row%3E%3C/Contacts%3E'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('createContact.json'), []);
            }));

        return new ZohoCreateContactConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|ZohoSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto(
            'POST',
            new Uri('https://crm.zoho.eu/crm/private/json/Contacts/%s?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi'))
        )->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        $system = $this->createMock(ZohoSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}