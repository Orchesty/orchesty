<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceCreateAdConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class FacebookaudienceCreateAdConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateAdConnectorTest extends KernelTestCaseAbstract
{

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
            'id'          => 'id',
            'client_id'   => 'cli',
            'mirror_id'   => 'mirr',
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

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        self::assertArrayHasKey('ad_id', json_decode($res->getData(), TRUE));
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
            'id'          => 'id',
            'client_id'   => 'cli',
            'mirror_id'   => 'mirr',
            'ad_data'     => [
                [
                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                    'link'        => 'http://example.com',
                    'title'       => 'titl',
                    'description' => 'desc',
                ],
                [
                    'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                    'link'        => 'http://example.com',
                    'title'       => 'titl2',
                    'description' => 'desc2',
                ],
            ],
            'status'      => 'PAUSED',
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        self::assertArrayHasKey('ad_id', json_decode($res->getData(), TRUE));
    }

    /**
     * @return FacebookaudienceCreateAdConnector
     * @throws Exception
     */
    private function createConnector(): FacebookaudienceCreateAdConnector
    {
        /** @var FacebookaudienceSystem $sys */
        $sys = $this->container->get('systems.facebookaudience');

        $sysInst = new SystemInstall();
        $sysInst->setSettings([
            OAuth2Provider::ACCESS_TOKEN       => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            FacebookaudienceSystem::AD_ACCOUNT => '103654000491411',
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

        /** @var CurlManager $curl */
        $curl = $this->container->get('hbpf.transport.curl_manager');

        return new FacebookaudienceCreateAdConnector($sys, $dm, $curl);
    }

}