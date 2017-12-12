<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateAudienceConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
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
 * Class FacebookaudienceCreateAudienceConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateAudienceConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode([
                'email'     => 'email@example.com',
                'firstName' => 'First Name',
                'lastName'  => 'Last Name',
            ]))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'attributes'     => [
                'type' => 'Contact',
                'url'  => '/services/data/v40.0/sobjects/Contact/0031I0000044AJ4QAM',
            ],
            'Id'             => '0031I0000044AJ4QAM',
            'IsDeleted'      => FALSE,
            'MasterRecordId' => NULL,
            'AccountId'      => NULL,
        ], $result);
    }

    /**
     * @return FacebookaudienceCreateAudienceConnector
     */
    private function getConnectorMock(): FacebookaudienceCreateAudienceConnector
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
                    new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('SalesforceSingleContactItem.json'), []);
            }));

        return new FacebookaudienceCreateAudienceConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return MockObject|FacebookaudienceSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('POST', new Uri('https://na73.salesforce.com')))->setHeaders([
            'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQCPw1z3hchprOA9t08aqFqrY4RdcjNaEmRBHf170davnludWxhbo4WjBgWptw9OSk1yi1c4lfZm5RVo8h9sNsoGEysPd',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $system = $this->createMock(FacebookaudienceSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}