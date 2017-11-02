<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoGetContactConnector;
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
     * @return ZohoGetContactConnector
     */
    private function getConnectorMock(): ZohoGetContactConnector
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
                    new Uri('https://crm.zoho.eu/crm/private/json/Contacts/getRecordById?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi&id=76762000000078126'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('getSingleContact.json'), []);
            }));

        return new ZohoGetContactConnector($this->getSystemMock(), $documentManager, $curlManager);
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