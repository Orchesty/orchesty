<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Document;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class HeartbeatDataTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Document
 */
final class HeartbeatDataTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData::setType
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData::getType
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData::setCount
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData::getCount
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData::toArray
     *
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $hearthBeatData = new HearthBeatData(1, '1');
        self::assertEquals('1', $hearthBeatData->getType());
        self::assertEquals(1, $hearthBeatData->getCount());
        self::assertEquals(['type' => '1', 'count' => 1], $hearthBeatData->toArray());
        $hearthBeatData->setType('2');
        $hearthBeatData->setCount(2);
        self::assertEquals('2', $hearthBeatData->getType());
        self::assertEquals(2, $hearthBeatData->getCount());
        self::assertEquals(['type' => '2', 'count' => 2], $hearthBeatData->toArray());
    }

}
