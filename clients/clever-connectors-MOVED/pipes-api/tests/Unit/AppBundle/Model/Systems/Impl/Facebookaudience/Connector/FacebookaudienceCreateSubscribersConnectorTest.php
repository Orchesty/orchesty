<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateSubscribersConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceCreateSubscribersConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateSubscribersConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceCreateSubscribersConnector::processAction()
     */
    public function testProcessAction(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'payload' => [
                'data' => [
                    'asdf1',
                    'asdf2',
                ],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => 'audience-123',
        ]);

        $result = Json::decode($this->getConnectorMock($systemInstall)->processAction($dto)->getData(), TRUE);

        $this->assertEquals(['id' => 'abc123'], $result);
    }

    /**
     * @covers FacebookaudienceCreateSubscribersConnector::processAction()
     */
    public function testProcessActionLimit(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'payload' => [
                'data' => [
                    'asdf1',
                    'asdf2',
                ],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => 'audience-123',
        ]);

        /** @var MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
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

        $connector = new FacebookaudienceCreateSubscribersConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $sender
        );

        $result = $connector->processAction($dto);

        $this->assertEquals(1004, $result->getHeader('pf-result-code'));
    }

    /**
     * @covers FacebookaudienceCreateSubscribersConnector::processAction()
     */
    public function testProcessActionMissingAudienceId(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'payload' => [
                'data' => [
                    'asdf1',
                    'asdf2',
                ],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->processAction($dto);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param bool          $creates
     *
     * @return FacebookaudienceCreateSubscribersConnector
     */
    private function getConnectorMock(
        SystemInstall $systemInstall,
        $creates = TRUE
    ): FacebookaudienceCreateSubscribersConnector
    {
        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);

        if ($creates) {
            $curlManager
                ->expects($this->at(0))
                ->method('send')
                ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                    $this->assertEquals(
                        new Uri('https://graph.facebook.com/v2.11/audience-123/users?access_token=access-token-123'),
                        $dto->getUri()
                    );

                    return new ResponseDto(200, 'OK', Json::encode(['id' => 'abc123']), []);
                }));
        }

        return new FacebookaudienceCreateSubscribersConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $curlManager
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return DocumentManager|MockObject
     */
    private function getDmMock(SystemInstall $systemInstall)
    {
        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstallRepository);

        return $documentManager;
    }

    /**
     * @return MockObject|FacebookaudienceSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('POST', new Uri('https://graph.facebook.com/v2.11')))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]);

        /** @var MockObject|FacebookaudienceSystem $system */
        $system = $this->createMock(FacebookaudienceSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}