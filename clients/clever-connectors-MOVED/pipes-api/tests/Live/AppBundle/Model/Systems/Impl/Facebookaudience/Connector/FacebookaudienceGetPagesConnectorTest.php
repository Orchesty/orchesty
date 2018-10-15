<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Exception;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetPagesConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetPagesConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceGetPagesConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $connector  = $this->container->get('hbpf.connector.facebookaudience-get-pages-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            OAuth2Provider::ACCESS_TOKEN       => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            FacebookaudienceSystem::AD_ACCOUNT => '103654000491411',
        ]));

        $pages = json_decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($pages));
        foreach ($pages as $key => $page) {
            $this->assertTrue(is_int($key));
            $this->assertTrue(is_string($page));
            break;
        }
    }

    /**
     * @covers FacebookaudienceGetPagesConnector::getPages()
     *
     * @throws Exception
     */
    public function testGetPages(): void
    {
        $processDto = $this->prepareConnectorProcessDto([
            OAuth2Provider::ACCESS_TOKEN       => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            FacebookaudienceSystem::AD_ACCOUNT => '103654000491411',
        ], [], [], TRUE);

        $data = json_decode($processDto->getData(), TRUE);
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemInstallRepository->find($data['system_install']['_id']);

        $connector = $this->container->get('hbpf.connector.facebookaudience-get-pages-connector');
        $pages = $connector->getPages($systemInstall);

        $this->assertTrue(is_array($pages));
        $this->assertTrue(count($pages) > 0);
        foreach ($pages as $key => $page) {
            $this->assertTrue(is_int($key));
            $this->assertTrue(is_string($page));
            break;
        }
    }

}