<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 3:47 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookGetPageConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookGetPageConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetPageConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testGetAccounts(): void
    {
        $response = new Response(200, [], $this->getRequest('FacebookPagesResponse.json'));

        $responseDto = new ResponseDto(
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getBody()->getContents(),
            $response->getHeaders()
        );

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManager $curlManager */
        $curlManager = $this->createMock(CurlManager::class);
        $curlManager->expects($this->at(0))->method('send')->willReturn($responseDto);

        $connector = new FacebookGetPageConnector($this->getSystem(), $this->getDm(), $curlManager);
        $result    = $connector->getAccounts($this->getSystemInstall());

        $this->assertCount(5, $result);
        $this->assertEquals('Cerv fiction', $result['787114551385792']);
    }

    /**
     *
     */
    public function testGetAccountsLimit(): void
    {
        /** @var MockObject|CurlManager $sender */
        $sender = $this->createMock(CurlManager::class);
        $sender
            ->expects($this->exactly(1))
            ->method('send')
            ->willReturnCallback(function (RequestDto $requestDto): void {
                $body = json_encode([
                    'error' => [
                        'code' => 4, // means: request limit reached
                    ],
                ]);
                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(400, [], $body));
            });

        $connector = new FacebookGetPageConnector($this->getSystem(), $this->getDm(), $sender);

        $this->expectException(CurlException::class);

        $connector->getAccounts($this->getSystemInstall());
    }

    /**
     * @return MockObject|DocumentManager
     */
    private function getDm()
    {
        return $this->createMock(DocumentManager::class);
    }

    /**
     * @return FacebookLeadsSystem|MockObject
     */
    private function getSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://test.neco'));
        $requestDto->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        /** @var MockObject|FacebookLeadsSystem $system */
        $system = $this->createMock(FacebookLeadsSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

    /**
     * @return SystemInstall|MockObject
     */
    private function getSystemInstall()
    {
        /** @var MockObject|SystemInstall $systemInstall */
        $systemInstall = $this->createMock(SystemInstall::class);
        $systemInstall->method('getSettings')->willReturn([
            OAuth2Provider::ACCESS_TOKEN => '987654321',
        ]);

        return $systemInstall;
    }

}