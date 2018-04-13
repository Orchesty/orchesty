<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector\FacebookaudienceImageUploadConnector;
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
 * Class FacebookaudienceImageUploadConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceImageUploadConnectorTest extends KernelTestCaseAbstract
{

    private const IMG = '../tests/Unit/AppBundle/Model/Systems/Impl/Facebookaudience/box.png';

    /**
     * @covers FacebookaudienceImageUploadConnector::processAction()
     *
     * @throws Exception
     */
    public function testConnector(): void
    {
        $appDir = $this->container->getParameter('kernel.root_dir');

        $data = [
            'ad_data' => [
                [
                    'image_content' => base64_encode(file_get_contents(sprintf('%s/%s', $appDir, self::IMG))),
                ],
            ],
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data))->setHeaders([]);

        $conn = $this->createConnector();
        $res  = $conn->processAction($dto);

        self::assertArrayHasKey('image_hash', json_decode($res->getData(), TRUE)['ad_data'][0]);
    }

    /**
     * @return FacebookaudienceImageUploadConnector
     * @throws Exception
     */
    private function createConnector(): FacebookaudienceImageUploadConnector
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

        return new FacebookaudienceImageUploadConnector($sys, $dm, $curl);
    }

}