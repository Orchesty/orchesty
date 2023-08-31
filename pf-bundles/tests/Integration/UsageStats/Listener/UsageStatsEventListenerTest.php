<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\UsageStats\Listener;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\PipesFramework\UsageStats\Event\BillingEvent;
use Hanaboso\PipesFramework\UsageStats\Listener\UsageStatsEventListener;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class UsageStatsEventListenerTest
 *
 * @package PipesFrameworkTests\Integration\UsageStats\Listener
 */
final class UsageStatsEventListenerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\UsageStats\Listener\UsageStatsEventListener::onProcessBillingEvent
     * @covers \Hanaboso\PipesFramework\UsageStats\Event\BillingEvent::checkData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::createFromBillingEvent
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setBillingData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::toArray
     *
     * @throws Exception
     */
    public function testListener(): void
    {
        $eventMock = new BillingEvent(EventTypeEnum::INSTALL, ['aid' => '1', 'euid' => '1']);
        $dml       = self::getContainer()->get('hbpf.database_manager_locator');

        $listener = new UsageStatsEventListener($dml, '1234');
        $listener->onProcessBillingEvent($eventMock);

        /** @var UsageStatsEvent $usageStatEvent */
        $usageStatEvent = $this->dm->getRepository(UsageStatsEvent::class)->findAll()[0];
        self::assertEquals(
            [
                'iid'     => '1234',
                'type'    => 'applinth_enduser_app_install',
                'version' => 1,
                'data'    => [
                    'aid'  => '1',
                    'euid' => '1',
                ],
                'created' => $usageStatEvent->getCreated()->format('Uu'),
            ],
            $usageStatEvent->toArray(),
        );
    }

}
