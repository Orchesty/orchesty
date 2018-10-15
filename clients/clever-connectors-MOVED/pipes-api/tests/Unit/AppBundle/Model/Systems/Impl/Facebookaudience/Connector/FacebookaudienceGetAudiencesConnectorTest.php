<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAudiencesConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
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
 * Class FacebookaudienceGetAudiencesConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAudiencesConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceGetAudiencesConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto = (new ProcessDto())->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT => 'ad-account-123',
        ]);

        $result = json_decode($this->getConnectorMock($systemInstall, TRUE)->processAction($dto)->getData(), TRUE);

        $this->assertEquals([
            '123' => 'name1',
            '456' => 'name2',
        ], $result);
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessActionLimit(): void
    {
        $dto = (new ProcessDto())->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT => 'ad-account-123',
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

        $connector = new FacebookaudienceGetAudiencesConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $sender
        );

        $result = $connector->processAction($dto);

        $this->assertEquals(1004, $result->getHeader('pf-result-code'));
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessActionMissingAdAccountId(): void
    {
        $dto = (new ProcessDto())->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->processAction($dto);
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::getAudiences()
     *
     * @throws Exception
     */
    public function testGetAudiences(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT => 'ad-account-123',
        ]);

        $result = $this->getConnectorMock($systemInstall)->getAudiences($systemInstall);

        $this->assertEquals([
            '123' => 'name1',
            '456' => 'name2',
        ], $result);
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::getAudiences()
     *
     * @throws Exception
     */
    public function testGetAudiencesLimit(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT => 'ad-account-123',
        ]);
        $systemInstall->setUser('user123');
        $systemInstall->setToken('token123');

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

        $connector = new FacebookaudienceGetAudiencesConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $sender
        );

        $this->expectException(CurlException::class);

        $connector->getAudiences($systemInstall);
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::getAudiences()
     *
     * @throws Exception
     */
    public function testGetAccountsMissingAdAccountId(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->getAudiences($systemInstall);
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::getAudiences()
     *
     * @throws Exception
     */
    public function testGetAccountsMissingAdAccountId2(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getConnectorMock($systemInstall, FALSE)->getAudiences($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param bool          $send
     *
     * @return FacebookaudienceGetAudiencesConnector
     * @throws Exception
     */
    private function getConnectorMock(SystemInstall $systemInstall, $send = TRUE): FacebookaudienceGetAudiencesConnector
    {
        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);

        if ($send) {
            $curlManager
                ->expects($this->at(0))
                ->method('send')
                ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                    $this->assertEquals(
                        new Uri('https://graph.facebook.com/v2.12/act_ad-account-123/customaudiences?fields=name&access_token=access-token-123'),
                        $dto->getUri()
                    );

                    return new ResponseDto(200, 'OK', Json::encode([
                        'data' => [
                            ['id' => '123', 'name' => 'name1'],
                            ['id' => '456', 'name' => 'name2'],
                        ],
                    ]), []);
                }));
        }

        return new FacebookaudienceGetAudiencesConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $curlManager
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return DocumentManager|MockObject
     * @throws Exception
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
     * @throws Exception
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('POST', new Uri('https://graph.facebook.com/v2.12')))
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