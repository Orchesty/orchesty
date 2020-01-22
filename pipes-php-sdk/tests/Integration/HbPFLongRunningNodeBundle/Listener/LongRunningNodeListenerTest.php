<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFLongRunningNodeBundle\Listener;

use Exception;
use Hanaboso\CommonsBundle\Event\ProcessStatusEvent;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Listener\LongRunningNodeListener;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class LongRunningNodeListenerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFLongRunningNodeBundle\Listener
 */
final class LongRunningNodeListenerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Listener\LongRunningNodeListener
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Listener\LongRunningNodeListener::onFinish()
     *
     * @throws Exception
     */
    public function testListener(): void
    {
        $this->prepData();
        /** @var EventDispatcher $dispatch */
        $dispatch = self::$container->get('event_dispatcher');
        $dispatch->dispatch(new ProcessStatusEvent('0', TRUE), ProcessStatusEvent::PROCESS_FINISHED);

        /** @var LongRunningNodeData[] $res */
        $res = $this->dm->getRepository(LongRunningNodeData::class)->findAll();
        self::assertEquals(1, count($res));
        self::assertEquals('2', $res[0]->getProcessId());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Listener\LongRunningNodeListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(['finished' => 'onFinish'], LongRunningNodeListener::getSubscribedEvents());
    }

    /**
     * @throws Exception
     */
    private function prepData(): void
    {
        /** @var LongRunningNodeData[] $docs */
        $docs = [];
        for ($i = 0; $i < 4; $i++) {
            $tmp = new LongRunningNodeData();
            $tmp->setProcessId((string) $i);
            $this->dm->persist($tmp);
            $docs[] = $tmp;
        }
        $docs[0]->setParentProcess('3');
        $docs[1]->setParentProcess('0');
        $docs[3]->setParentProcess('1');

        $this->dm->flush();
    }

}
