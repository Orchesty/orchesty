<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector\ZohoUpdateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @return ZohoUpdateContactConnector
     */
    private function getConnectorMock(): ZohoUpdateContactConnector
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                $this->assertEquals(
                    new Uri('https://crm.zoho.eu/crm/private/json/Contacts/updateRecords?authtoken=05361930f1c8c009d9a1e30e07b23126&scope=crmapi&id=76762000000080046&newFormat=1&xmlData=<Contacts><row no="1"><FL val="cm_unsubscribe">1</FL></row></Contacts>'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('updateContact.json'), []);
            }));

        return new ZohoUpdateContactConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return MockObject|ZohoSystem
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