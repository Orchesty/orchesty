<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Listeners;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Event\TopologyEvent;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class EventListenerTest
 *
 * @package Tests\Integration\AppBundle\Listeners
 */
class EventListenerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     *
     */
    public function testTopologyEvent(): void
    {
        $web = new Webhook();
        $web->setTopologyName('ttop')
            ->setUser('tuser')
            ->setSystemKey('null.user.group')
            ->setWebhookId('');
        $this->dm->persist($web);

        $sysInstall = new SystemInstall();
        $sysInstall->setUser('tuser')
            ->setSystem('null.user.group');
        $this->dm->persist($sysInstall);

        $this->dm->flush();

        $curl = $this->getMockBuilder(CurlManagerInterface::class)->disableOriginalConstructor()->getMock();
        $curl->method('send')->willReturn(new ResponseDto(200, '', '', []));

        $this->container->set('hbpf.transport.curl_manager', $curl);

        $dispatcher = $this->container->get('event_dispatcher');

        self::assertNotEmpty($this->dm->getRepository(Webhook::class)->findBy(['_id' => $web->getId()]));
        $dispatcher->dispatch(TopologyEvent::EVENT, new TopologyEvent('ttop'));
        self::assertEmpty($this->dm->getRepository(Webhook::class)->findBy(['_id' => $web->getId()]));
    }

}