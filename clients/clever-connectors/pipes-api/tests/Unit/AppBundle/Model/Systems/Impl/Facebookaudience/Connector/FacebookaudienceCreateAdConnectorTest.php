<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateAdConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class FacebookaudienceCreateAdConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateAdConnectorTest extends KernelTestCaseAbstract
{

    private const ACC    = '103654000491411';
    private const ACCESS = 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD';

    /**
     * @covers FacebookaudienceCreateAdConnector::processAction()
     * @covers FacebookaudienceCreateAdConnector::createSingleImageAdset()
     *
     * @throws Exception
     */
    public function testSingleImageAd(): void
    {
        $data = [
            'name'        => 'tttest',
            'page_id'     => '448171238945439',
            'audience_id' => '120330000252930708',
            'adset_id'    => '120330000253677908',
            'client_id'   => 'cli',
            'mirror_id'   => 'mirr',
            'id'          => 'db_id',
            'ad_data'     => [
                [
                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                    'link'        => 'http://example.com',
                    'title'       => 'titl',
                    'description' => 'desc',
                ],
            ],
            'status'      => 'PAUSED',
        ];

        $clb = function (RequestDto $dto, array $options): ResponseDto {
            $expt = new RequestDto('POST',
                new Uri(sprintf('https://graph.facebook.com/v2.12/act_%s/ads', self::ACC)));
            $expt->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'multipart/form-data',
            ]);

            self::assertEquals($expt, $dto);
            self::assertEquals([
                'access_token' => self::ACCESS,
                'name'         => 'tttest',
                'status'       => 'PAUSED',
                'adset_id'     => '120330000253677908',
                'creative'     => [
                    'title'             => 'titl',
                    'body'              => 'desc',
                    'object_story_spec' => [
                        'link_data' => [
                            'image_hash' => '925e86d2d195a193cb2446c294960ea0',
                            'link'       => 'http://example.com',
                            'message'    => 'desc',
                        ],
                        'page_id'   => '448171238945439',
                    ],
                ],
            ], $options['form_params']);

            return new ResponseDto(200, '', '{"id": "asd"}', []);
        };

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector($clb);
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('ref_id', $body);
        self::assertEquals('asd', $body['ref_id']);
        self::assertArrayNotHasKey('ad_data', $body);
    }

    /**
     * @covers FacebookaudienceCreateAdConnector::processAction()
     * @covers FacebookaudienceCreateAdConnector::createCarouselAdset()
     *
     * @throws Exception
     */
    public function testCarouselAd(): void
    {
        $data = [
            'name'        => 'tttest',
            'page_id'     => '448171238945439',
            'audience_id' => '120330000252930708',
            'adset_id'    => '120330000253677908',
            'client_id'   => 'cli',
            'mirror_id'   => 'mirr',
            'id'          => 'db_id',
            'ad_data'     => [
                [
                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                    'link'        => 'http://example.com',
                    'title'       => 'titl',
                    'description' => 'desc',
                ],
                [
                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                    'link'        => 'http://example2.com',
                    'title'       => 'titl2',
                    'description' => 'desc2',
                ],
            ],
            'status'      => 'PAUSED',
        ];

        $clb = function (RequestDto $dto, array $options): ResponseDto {
            $expt = new RequestDto('POST',
                new Uri(sprintf('https://graph.facebook.com/v2.12/act_%s/ads', self::ACC)));
            $expt->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'multipart/form-data',
            ]);

            self::assertEquals($expt, $dto);
            self::assertEquals([
                'access_token' => self::ACCESS,
                'name'         => 'tttest',
                'status'       => 'PAUSED',
                'adset_id'     => '120330000253677908',
                'creative'     => [
                    'object_story_spec' => [
                        'link_data' => [
                            'child_attachments' => [
                                [
                                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                                    'link'        => 'http://example.com',
                                    'name'        => 'titl',
                                    'description' => 'desc',
                                ],
                                [
                                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                                    'link'        => 'http://example2.com',
                                    'name'        => 'titl2',
                                    'description' => 'desc2',
                                ],
                            ],
                            'link'              => 'http://example.com',
                        ],
                        'page_id'   => '448171238945439',
                    ],
                ],
            ], $options['form_params']);

            return new ResponseDto(200, '', '{"id": "asd"}', []);
        };

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector($clb);
        $res  = $conn->processAction($dto);

        $body = json_decode($res->getData(), TRUE);
        self::assertArrayHasKey('ref_id', $body);
        self::assertEquals('asd', $body['ref_id']);
        self::assertArrayNotHasKey('ad_data', $body);
    }

    /**
     * @param callable $callback
     *
     * @return FacebookaudienceCreateAdConnector
     * @throws Exception
     */
    private function createConnector(callable $callback): FacebookaudienceCreateAdConnector
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

        /** @var SystemInstallRepository|MockObject $sysRepo */
        $sysRepo = $this->createMock(SystemInstallRepository::class);
        $sysRepo->method('getSystemInstallFromHeaders')->willReturn($sysInst);

        /** @var AudienceMirrorRepository|MockObject $mirrRepo */
        $mirrRepo = $this->createMock(AudienceMirrorRepository::class);
        $mirrRepo->method('find')->willReturn(new AudienceMirror());

        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->at(0))
            ->method('getRepository')->willReturn($sysRepo);
        $dm->expects($this->at(1))
            ->method('getRepository')->willReturn($mirrRepo);

        /** @var CurlManager|MockObject $curl */
        $curl = $this->createMock(CurlManager::class);
        $curl->method('send')->willReturnCallback($callback);

        return new FacebookaudienceCreateAdConnector($sys, $dm, $curl);
    }

}