<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Document;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\BillingData;
use Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class UsageStatsEventTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Document
 */
final class UsageStatsEventTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::getId
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setId
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::getType
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setType
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::getVersion
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setVersion
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::getData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setHeartBeatData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::getSent
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent::setSent
     *
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $usageStatsEvent = new UsageStatsEvent('1', '1');
        self::assertEquals('1', $usageStatsEvent->getIid());
        self::assertEquals('1', $usageStatsEvent->getType());
        $usageStatsEvent->setSent(1);
        $usageStatsEvent->setVersion(1);
        $usageStatsEvent->setType('2');
        $usageStatsEvent->setIid('2');
        $usageStatsEvent->setHeartBeatData(new HearthBeatData(1, '1'));
        $usageStatsEvent->setData(['2']);
        $usageStatsEvent->setBillingData(new BillingData('1', '1'));
        $arr            = $usageStatsEvent->toArray();
        $arr['created'] = '1';
        self::assertEquals(
            [
                'iid'     => '2',
                'type'    => '2',
                'version' => 1,
                'data'    => ['aid' => '1', 'euid' => '1'],
                'created' => '1',
            ],
            $arr,
        );
    }

}
