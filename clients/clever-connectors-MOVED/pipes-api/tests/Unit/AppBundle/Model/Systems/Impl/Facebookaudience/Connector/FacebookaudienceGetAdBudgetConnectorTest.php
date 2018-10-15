<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceGetAdBudgetConnector;
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
 * Class FacebookaudienceGetAdBudgetConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAdBudgetConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceGetAdBudgetConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $dto = (new ProcessDto())->setHeaders([])
            ->setData('{"ad_id":"adId"}');

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'access-token-123',
            FacebookaudienceSystem::AD_ACCOUNT => 'ad-account-123',
        ]);

        $result = json_decode($this->getConnectorMock($systemInstall)->processAction($dto)->getData(), TRUE);

        self::assertEquals([
            'ad_id'            => 'adId',
            'adset_id'         => 'adsetId',
            'spend'            => '55',
            'lifetime_budget'  => '0',
            'daily_budget'     => '2500',
            'budget_remaining' => '1500',
        ], $result);
    }

    /**
     * @covers FacebookaudienceGetAdBudgetConnector::getAdBudget()
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

        $result = $this->getConnectorMock($systemInstall)->getAdBudget($systemInstall, 'adId');

        self::assertEquals([
            'ad_id'            => 'adId',
            'adset_id'         => 'adsetId',
            'spend'            => '55',
            'lifetime_budget'  => '0',
            'daily_budget'     => '2500',
            'budget_remaining' => '1500',
        ], $result);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return FacebookaudienceGetAdBudgetConnector
     * @throws Exception
     */
    private function getConnectorMock(SystemInstall $systemInstall): FacebookaudienceGetAdBudgetConnector
    {
        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);

        $curlManager
            ->expects($this->at(0))
            ->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                self::assertEquals(
                    new Uri('https://graph.facebook.com/v2.12/adId/insights?fields=adset_id,spend&access_token=access-token-123'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', json_encode([
                    'data' => [
                        [
                            'adset_id' => 'adsetId',
                            'spend'    => '55',
                        ],
                    ],
                ]), []);
            }));
        $curlManager
            ->expects($this->at(1))
            ->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                self::assertEquals(
                    new Uri('https://graph.facebook.com/v2.12/adsetId?fields=lifetime_budget,daily_budget,budget_remaining&access_token=access-token-123'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', json_encode([
                    'lifetime_budget'  => '0',
                    'daily_budget'     => '2500',
                    'budget_remaining' => '1500',
                    'id'               => 'adsetId',
                ]), []);
            }));

        return new FacebookaudienceGetAdBudgetConnector(
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
        $system->expects($this->at(0))
            ->method('getRequestDto')->willReturn($requestDto);
        $system->expects($this->at(1))
            ->method('getRequestDto')->willReturn(clone $requestDto);

        return $system;
    }

}