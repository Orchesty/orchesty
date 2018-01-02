<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateAudienceConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateSubscribersConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
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
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
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
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
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
     *
     * @param bool          $creates
     *
     * @return FacebookaudienceCreateSubscribersConnector
     */
    private function getConnectorMock(
        SystemInstall $systemInstall,
        $creates = TRUE
    ): FacebookaudienceCreateSubscribersConnector
    {
        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstallRepository);

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

        return new FacebookaudienceCreateSubscribersConnector($this->getSystemMock(), $documentManager, $curlManager);
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