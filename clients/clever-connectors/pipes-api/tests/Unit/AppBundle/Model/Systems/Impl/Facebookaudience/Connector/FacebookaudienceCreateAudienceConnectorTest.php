<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateAudienceConnector;
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
        $dto = (new ProcessDto())->setData(Json::encode([
            'data' => [
                ['name' => 'list name 1'],
                ['name' => 'list name 2'],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT      => 'ad-account-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => FacebookaudienceSystem::CREATE_NEW,
            FacebookaudienceSystem::NEW_LIST        => 'new list name',
        ]);

        $result = Json::decode($this->getConnectorMock($systemInstall)->processAction($dto)->getData(), TRUE);

        $this->assertEquals(['id' => 'abc123'], $result);
    }

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessActionListExists(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'data' => [
                ['name' => 'new list name'],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT      => 'ad-account-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => FacebookaudienceSystem::CREATE_NEW,
            FacebookaudienceSystem::NEW_LIST        => 'new list name',
        ]);

        $result = Json::decode($this->getConnectorMock($systemInstall, FALSE)->processAction($dto)->getData(), TRUE);

        $this->assertEquals(['data' => [['name' => 'new list name']]], $result);
    }

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessActionUseExisting(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'data' => [
                ['name' => 'new list name'],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT      => 'ad-account-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => 'existing-audience-123',
        ]);

        $result = Json::decode($this->getConnectorMock($systemInstall, FALSE)->processAction($dto)->getData(), TRUE);

        $this->assertEquals(['data' => [['name' => 'new list name']]], $result);
    }

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessActionMissingData(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT      => 'ad-account-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => 'existing-audience-123',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->processAction($dto);
    }

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessActionMissingAudienceId(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'data' => [
                ['name' => 'new list name'],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT => 'ad-account-123',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->processAction($dto);
    }

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessActionMissingAudienceName(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'data' => [
                ['name' => 'new list name'],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT      => 'ad-account-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => FacebookaudienceSystem::CREATE_NEW,
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->processAction($dto);
    }

    /**
     * @covers FacebookaudienceCreateAudienceConnector::processAction()
     */
    public function testProcessActionMissingAdAccountId(): void
    {
        $dto = (new ProcessDto())->setData(Json::encode([
            'data' => [
                ['name' => 'list name 1'],
            ],
        ]))->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN            => 'access-token-123',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => FacebookaudienceSystem::CREATE_NEW,
            FacebookaudienceSystem::NEW_LIST        => 'new list name',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->processAction($dto);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param bool          $creates
     *
     * @return FacebookaudienceCreateAudienceConnector
     */
    private function getConnectorMock(
        SystemInstall $systemInstall,
        $creates = TRUE
    ): FacebookaudienceCreateAudienceConnector
    {
        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstallRepository);

        if ($creates) {
            $documentManager
                ->expects($this->at(0))
                ->method('flush')
                ->willReturn(TRUE);
        }

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);

        if ($creates) {
            $curlManager
                ->expects($this->at(0))
                ->method('send')
                ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                    $this->assertEquals(
                        new Uri('https://graph.facebook.com/v2.11/ad-account-123/customaudiences?access_token=access-token-123'),
                        $dto->getUri()
                    );

                    return new ResponseDto(200, 'OK', Json::encode(['id' => 'abc123']), []);
                }));
        }

        return new FacebookaudienceCreateAudienceConnector($this->getSystemMock($creates), $documentManager,
            $curlManager);
    }

    /**
     * @param bool $creates
     *
     * @return MockObject|FacebookaudienceSystem
     */
    private function getSystemMock($creates = TRUE)
    {
        $requestDto = (new RequestDto('POST', new Uri('https://graph.facebook.com/v2.11')))
            ->setHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]);

        /** @var MockObject|FacebookaudienceSystem $system */
        $system = $this->createMock(FacebookaudienceSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        if ($creates) {
            $system
                ->expects($this->at(0))
                ->method('setSettings')
                ->willReturn(TRUE);
        }

        return $system;
    }

}