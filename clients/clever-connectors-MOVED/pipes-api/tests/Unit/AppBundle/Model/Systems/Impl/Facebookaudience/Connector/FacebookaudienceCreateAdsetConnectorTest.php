<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateAdsetConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class FacebookaudienceCreateAdsetConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateAdsetConnectorTest extends KernelTestCaseAbstract
{

    private const ACC    = '103654000491411';
    private const ACCESS = 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD';

    /**
     * @covers FacebookaudienceCreateAdsetConnector::processAction()
     *
     * @throws Exception
     */
    public function testConnector(): void
    {
        $data = [
            'name'          => 'tttest',
            'campaign_id'   => 120330000253356108,
            'billing_event' => 'LINK_CLICKS',
            'bid_amount'    => 1,
            'daily_budget'  => 5000,
            'page_id'       => '448171238945439',
            'audience_id'   => '120330000252930708',
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('adset_id', $body);
        self::assertEquals('asd', $body['adset_id']);
        self::assertArrayNotHasKey('campaign_id', $body);
        self::assertArrayNotHasKey('billing_event', $body);
        self::assertArrayNotHasKey('daily_budget', $body);
    }

    /**
     * @return FacebookaudienceCreateAdsetConnector
     * @throws Exception
     */
    private function createConnector(): FacebookaudienceCreateAdsetConnector
    {
        /** @var FacebookaudienceSystem $sys */
        $sys = $this->ownContainer->get('systems.facebookaudience');

        $sysInst = new SystemInstall();
        $sysInst->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => self::ACCESS,
            FacebookaudienceSystem::AD_ACCOUNT => self::ACC,
        ])
            ->setToken('tkn')
            ->setUser('123')
            ->setSystem('facebookaudience');

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sysInst);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        /** @var CurlManager|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManager::class);
        $curl->method('send')->willReturnCallback(
            function (RequestDto $dto, array $options): ResponseDto {
                $expt = new RequestDto('POST',
                    new Uri(sprintf('https://graph.facebook.com/v2.12/act_%s/adsets', self::ACC)));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'multipart/form-data',
                ]);

                self::assertEquals($expt, $dto);
                self::assertEquals([
                    'access_token'      => self::ACCESS,
                    'name'              => 'tttest',
                    'billing_event'     => 'LINK_CLICKS',
                    'daily_budget'      => 5000,
                    'optimization_goal' => 'LINK_CLICKS',
                    'bid_amount'        => 1,
                    'campaign_id'       => 120330000253356108,
                    'promoted_object'   => json_encode([
                        'page_id' => '448171238945439',
                    ]),
                    'targeting'         => json_encode([
                        'custom_audiences'    => ['120330000252930708'],
                        'publisher_platforms' => ['facebook'],
                    ]),
                ], $options['form_params']);

                return new ResponseDto(200, '', '{"id": "asd"}', []);
            }
        );

        return new FacebookaudienceCreateAdsetConnector($sys, $dm, $curl);
    }

}