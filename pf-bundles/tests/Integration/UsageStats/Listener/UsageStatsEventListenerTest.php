<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\UsageStats\Listener;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\PipesFramework\UsageStats\Event\BillingEvent;
use Hanaboso\PipesFramework\UsageStats\Listener\UsageStatsEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class UsageStatsEventListenerTest
 *
 * @package PipesFrameworkTests\Integration\UsageStats\Listener
 */
#[CoversClass(UsageStatsEventListener::class)]
#[CoversClass(BillingEvent::class)]
#[CoversClass(UsageStatsEvent::class)]
final class UsageStatsEventListenerTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testListener(): void
    {
        $eventMock = new BillingEvent(EventTypeEnum::INSTALL->value, ['aid' => '1', 'euid' => '1']);
        $dml       = self::getContainer()->get('hbpf.database_manager_locator');

        $listener = new UsageStatsEventListener($dml, '1234');
        $listener->onProcessBillingEvent($eventMock);

        /** @var UsageStatsEvent $usageStatEvent */
        $usageStatEvent = $this->dm->getRepository(UsageStatsEvent::class)->findAll()[0];
        self::assertEquals(
            [
                'created' => $usageStatEvent->getCreated()->format('Uu'),
                'data'    => [
                    'aid'  => '1',
                    'euid' => '1',
                ],
                'iid'     => '1234',
                'type'    => 'applinth_enduser_app_install',
                'version' => 1,
            ],
            $usageStatEvent->toArray(),
        );
    }

}
