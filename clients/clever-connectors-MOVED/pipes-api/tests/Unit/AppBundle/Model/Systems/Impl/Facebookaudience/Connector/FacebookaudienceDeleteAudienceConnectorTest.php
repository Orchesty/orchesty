<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceDeleteAudienceConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceDeleteAudienceConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceDeleteAudienceConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceDeleteAudienceConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto = (new ProcessDto())->setHeaders([])
            ->setData('{"ref_id":"audienceId"}');

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $result = json_decode($this->getConnectorMock($systemInstall, TRUE)->processAction($dto)->getData(), TRUE);

        $this->assertEquals([
            'ref_id' => 'audienceId',
        ], $result);
    }

    /**
     * @covers FacebookaudienceDeleteAudienceConnector::deleteAudience()
     *
     * @throws Exception
     */
    public function testDeleteAudience(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN => 'access-token-123',
        ]);

        $result = $this->getConnectorMock($systemInstall)->deleteAudience($systemInstall, 'audienceId', '123');

        self::assertTrue($result);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param bool          $send
     *
     * @return FacebookaudienceDeleteAudienceConnector
     * @throws Exception
     */
    private function getConnectorMock(SystemInstall $systemInstall,
                                      $send = TRUE): FacebookaudienceDeleteAudienceConnector
    {
        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);

        if ($send) {
            $curlManager
                ->expects($this->at(0))
                ->method('send')
                ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                    $this->assertEquals(
                        new Uri('https://graph.facebook.com/v2.12/audienceId?access_token=access-token-123'),
                        $dto->getUri()
                    );

                    return new ResponseDto(200, 'OK', '', []);
                }));
        }

        return new FacebookaudienceDeleteAudienceConnector(
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