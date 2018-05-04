<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Exception;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceDeleteAudienceConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
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
        $mirr      = $this->prepData();
        $connector = $this->container->get('hbpf.connector.facebookaudience-delete-audience-connector');
        $connector->processAction($this->prepareConnectorProcessDto([
            OAuth2Provider::ACCESS_TOKEN => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
        ], [
            'ref_id'    => '120330000255843108',
            'mirror_id' => $mirr->getId(),
        ]));

        $this->assert($mirr->getId());
    }

    /**
     * @covers FacebookaudienceDeleteAudienceConnector::deleteAudience()
     *
     * @throws Exception
     */
    public function testDeleteAudience(): void
    {
        $mirr       = $this->prepData();
        $processDto = $this->prepareConnectorProcessDto([
            OAuth2Provider::ACCESS_TOKEN       => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            FacebookaudienceSystem::AD_ACCOUNT => '103654000491411',
        ], [], [], TRUE);

        $data = json_decode($processDto->getData(), TRUE);
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemInstallRepository->find($data['system_install']['_id']);

        $connector = $this->container->get('hbpf.connector.facebookaudience-delete-audience-connector');

        self::assertTrue($connector->deleteAudience($systemInstall, '120330000255843208', $mirr->getId()));
        $this->assert($mirr->getId());
    }

    /**
     * @return AudienceMirror
     */
    private function prepData(): AudienceMirror
    {
        $mirr = new AudienceMirror();
        $this->dm->persist($mirr);
        $this->dm->flush();

        return $mirr;
    }

    /**
     * @param string $id
     */
    private function assert(string $id): void
    {
        $repo = $this->dm->getRepository(AudienceMirror::class);
        self::assertNull($repo->find($id));
    }

}