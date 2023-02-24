<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Event;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\PipesFramework\UsageStats\Event\BillingEvent;
use LogicException;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class BillingEventTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Event
 */
final class BillingEventTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\UsageStats\Event\BillingEvent
     * @covers \Hanaboso\PipesFramework\UsageStats\Event\BillingEvent::getData
     * @covers \Hanaboso\PipesFramework\UsageStats\Event\BillingEvent::setData
     * @covers \Hanaboso\PipesFramework\UsageStats\Event\BillingEvent::getType
     * @covers \Hanaboso\PipesFramework\UsageStats\Event\BillingEvent::setType
     *
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $billingEvent = new BillingEvent(EventTypeEnum::INSTALL->value, ['aid' => '1', 'euid' => '1']);
        self::assertEquals(EventTypeEnum::INSTALL->value, $billingEvent->getType());
        self::assertEquals(['aid' => '1', 'euid' => '1'], $billingEvent->getData()->toArray());
        $billingEvent->setType(EventTypeEnum::UNINSTALL->value);
        self::assertEquals(EventTypeEnum::UNINSTALL->value, $billingEvent->getType());
        $billingEvent->setData(['aid' => '2', 'euid' => '2']);
        self::assertEquals(['aid' => '2', 'euid' => '2'], $billingEvent->getData()->toArray());

        self::expectException(LogicException::class);
        self::expectExceptionMessage('Missing key aid and/or euid in data field!');
        new BillingEvent(EventTypeEnum::INSTALL->value, ['aid' => '1']);
    }

}
