<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Document;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class HeartbeatDataTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Document
 */
#[CoversClass(HearthBeatData::class)]
final class HeartbeatDataTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $hearthBeatData = new HearthBeatData(1, '1');
        self::assertSame('1', $hearthBeatData->getType());
        self::assertSame(1, $hearthBeatData->getCount());
        self::assertEquals(['type' => '1', 'count' => 1], $hearthBeatData->toArray());
        $hearthBeatData->setType('2');
        $hearthBeatData->setCount(2);
        self::assertSame('2', $hearthBeatData->getType());
        self::assertSame(2, $hearthBeatData->getCount());
        self::assertEquals(['type' => '2', 'count' => 2], $hearthBeatData->toArray());
    }

}
