<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 3:47 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookGetLeadformConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookGetLeadformConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetLeadformConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testGetLeadForms(): void
    {
        $responsePage = new Response(200, [], $this->getRequest('FacebookPageResponse.json'));

        $responsePageDto = new ResponseDto(
            $responsePage->getStatusCode(),
            $responsePage->getReasonPhrase(),
            $responsePage->getBody()->getContents(),
            $responsePage->getHeaders()
        );

        $response = new Response(200, [], $this->getRequest('FacebookFormsResponse.json'));

        $responseDto = new ResponseDto(
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getBody()->getContents(),
            $response->getHeaders()
        );

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManager $curlManager */
        $curlManager = $this->createMock(CurlManager::class);
        $curlManager->expects($this->at(0))->method('send')->willReturn($responsePageDto);
        $curlManager->expects($this->at(1))->method('send')->willReturn($responseDto);

        $connector = new FacebookGetLeadformConnector($this->getSystem(), $this->getDm(), $curlManager);
        $result    = $connector->getLeadForms($this->getSystemInstall(), [FacebookLeadsSystem::PAGE_ID => 'page_id']);

        $expected = [
            0 => [
                'form_id'   => '148856735852557',
                'form_name' => 'test form',
                'list'      => NULL,
            ],
            1 => [
                'form_id'   => '505108016512972',
                'form_name' => 'test form-copy',
                'list'      => NULL,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     *
     */
    public function testGetLeadFormsLimit(): void
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

        $connector = new FacebookGetLeadformConnector($this->getSystem(), $this->getDm(), $sender);

        $this->expectException(CurlException::class);

        $connector->getLeadForms($this->getSystemInstall(), [FacebookLeadsSystem::PAGE_ID => 'page_id']);
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
            OAuth2Provider::ACCESS_TOKEN => '123456',
            FacebookLeadsSystem::PAGE_ID => 'page_id',
            SystemInstall::FORMS         => [
                0 => [
                    'form_id'   => '505108016512973',
                    'form_name' => 'test form-copy',
                    'list'      => 'FacebookLeads',
                ],
                1 => [
                    'form_id'   => '148856735852557',
                    'form_name' => 'test form',
                    'list'      => NULL,
                ],
            ],
        ]);

        return $systemInstall;
    }

}