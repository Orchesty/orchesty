<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceImageUploadConnector;
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
 * Class FacebookaudienceUploadImageConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceUploadImageConnectorTest extends KernelTestCaseAbstract
{

    private const IMG    = '/../box.png';
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
            'ad_data' => [
                [
                    'image_content' => base64_encode(file_get_contents(__DIR__ . self::IMG)),
                ],
            ],
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('image_hash', $body['ad_data'][0]);
        self::assertArrayNotHasKey('image_content', $body['ad_data'][0]);
    }

    /**
     * @return FacebookaudienceImageUploadConnector
     * @throws Exception
     */
    private function createConnector(): FacebookaudienceImageUploadConnector
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
                    new Uri(sprintf('https://graph.facebook.com/v2.12/act_%s/adimages', self::ACC)));
                $expt->setHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'multipart/form-data',
                ]);

                self::assertEquals($expt, $dto);
                self::assertEquals([
                    'access_token' => self::ACCESS,
                    'bytes'        => $options['form_params']['bytes'],
                ], $options['form_params']);

                return new ResponseDto(200, '',
                    '{"images":{"bytes":{"hash":"08fff073f9a4f55a3631c09ee97b7670","url":"https:\/\/scontent.xx.fbcdn.net\/v\/t45.1600-4\/29963706_120330000253365308_3863463433693298688_n.png?_nc_cat=0&oh=239eebb081cc9797d0cb19c5f1522be1&oe=5B2B6EC5"}}}',
                    []);
            }
        );

        return new FacebookaudienceImageUploadConnector($sys, $dm, $curl);
    }

}