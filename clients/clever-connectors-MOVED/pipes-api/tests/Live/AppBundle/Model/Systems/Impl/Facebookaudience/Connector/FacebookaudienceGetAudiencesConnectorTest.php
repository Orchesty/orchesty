<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Exception;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetAudiencesConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAudiencesConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceGetAudiencesConnector::processAction()
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $connector  = $this->container->get('hbpf.connector.facebookaudience-get-audiences-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            OAuth2Provider::ACCESS_TOKEN       => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            FacebookaudienceSystem::AD_ACCOUNT => '103654000491411',
        ]));

        $data = Json::decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($data));
        foreach ($data as $key => $audience) {
            $this->assertTrue(is_int($key));
            $this->assertTrue(is_string($audience));
            break;
        }
    }

    /**
     * @covers FacebookaudienceGetAudiencesConnector::getAudiences()
     *
     * @throws Exception
     */
    public function testGetAudiences(): void
    {
        $processDto = $this->prepareConnectorProcessDto([
            OAuth2Provider::ACCESS_TOKEN       => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            FacebookaudienceSystem::AD_ACCOUNT => '103654000491411',
        ], [], [], TRUE);

        $data = Json::decode($processDto->getData(), TRUE);
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemInstallRepository->find($data['system_install']['_id']);

        $connector = $this->container->get('hbpf.connector.facebookaudience-get-audiences-connector');
        $audiences = $connector->getAudiences($systemInstall);

        $this->assertTrue(is_array($audiences));
        $this->assertTrue(count($audiences) > 0);
        foreach ($audiences as $key => $audience) {
            $this->assertTrue(is_int($key));
            $this->assertTrue(is_string($audience));
            break;
        }
    }

}