<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateCampaignConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class FacebookaudienceCreateCampaignConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateCampaignConnectorTest extends KernelTestCaseAbstract
{

    private const ACC    = '103654000491411';
    private const ACCESS = 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD';

    /**
     * @covers FacebookaudienceImageUploadConnector::processAction()
     *
     * @throws Exception
     */
    public function testConnector(): void
    {
        $data = [
            'campaign_objective' => 'LINK_CLICKS',
            'name'               => 'tttest',
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('campaign_id', $body);
        self::assertEquals('asd', $body['campaign_id']);
        self::assertArrayNotHasKey('campaign_objective', $body);
    }

    /**
     * @return FacebookaudienceCreateCampaignConnector
     * @throws Exception
     */
    private function createConnector(): FacebookaudienceCreateCampaignConnector
    {
        /** @var FacebookaudienceSystem $sys */
        $sys = $this->container->get('systems.facebookaudience');

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
                    new Uri(sprintf('https://graph.facebook.com/v2.12/act_%s/campaigns', self::ACC)));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'multipart/form-data',
                ]);

                self::assertEquals($expt, $dto);
                self::assertEquals([
                    'access_token' => self::ACCESS,
                    'name'         => 'tttest',
                    'status'       => 'ACTIVE',
                    'objective'    => 'LINK_CLICKS',
                ], $options['form_params']);

                return new ResponseDto(200, '', '{"id": "asd"}', []);
            }
        );

        return new FacebookaudienceCreateCampaignConnector($sys, $dm, $curl);
    }

}