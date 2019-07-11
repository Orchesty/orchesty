<?php declare(strict_types=1);

namespace Tests\Integration\HbPFLongRunningNodeBundle\Listener;

use Exception;
use Hanaboso\CommonsBundle\Event\ProcessStatusEvent;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeListenerTest
 *
 * @package Tests\Integration\HbPFLongRunningNodeBundle\Listener
 */
final class LongRunningNodeListenerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers LongRunningNodeListener::onFinish()
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
