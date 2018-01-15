<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAccountsConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetAccountsConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAccountsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceGetAccountsConnector::processAction()
     */
    public function testProcessAction(): void
    {
        $dto = (new ProcessDto())->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $result = Json::decode($this->getConnectorMock($systemInstall)->processAction($dto)->getData(), TRUE);

        $this->assertEquals([
            'data' => [
                ['id' => '123', 'name' => 'name1'],
                ['id' => '456', 'name' => 'name2'],
            ],
        ], $result);
    }

    /**
     * @covers FacebookaudienceGetAccountsConnector::processAction()
     */
    public function testProcessActionLimit(): void
    {
        $dto = (new ProcessDto())->setHeaders([]);

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
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

        $connector = new FacebookaudienceGetAccountsConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $sender
        );

        $result = $connector->processAction($dto);

        $this->assertEquals(1004, $result->getHeader('pf-result-code'));
    }

    /**
     * @covers FacebookaudienceGetAccountsConnector::getAccounts()
     */
    public function testGetAccounts(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $result = $this->getConnectorMock($systemInstall)->getAccounts($systemInstall);

        $this->assertEquals([
            '123' => 'name1',
            '456' => 'name2',
        ], $result);
    }

    /**
     * @covers FacebookaudienceGetAccountsConnector::getAccounts()
     */
    public function testGetAccountsLimit(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
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

        $connector = new FacebookaudienceGetAccountsConnector(
            $this->getSystemMock(),
            $this->getDmMock($systemInstall),
            $sender
        );

        $this->expectException(CurlException::class);

        $connector->getAccounts($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return FacebookaudienceGetAccountsConnector
     */
    private function getConnectorMock(SystemInstall $systemInstall): FacebookaudienceGetAccountsConnector
    {
        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager
            ->expects($this->at(0))
            ->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                $this->assertEquals(
                    new Uri('https://graph.facebook.com/v2.11/me/adaccounts?fields=name&access_token=access-token-123'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', Json::encode([
                    'data' => [
                        ['id' => '123', 'name' => 'name1'],
                        ['id' => '456', 'name' => 'name2'],
                    ],
                ]), []);
            }));

        return new FacebookaudienceGetAccountsConnector(
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