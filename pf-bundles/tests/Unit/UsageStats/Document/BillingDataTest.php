<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Document;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\BillingData;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class BillingDataTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Document
 */
final class BillingDataTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\BillingData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\BillingData::setEuid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\BillingData::setAid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\BillingData::getEuid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\BillingData::getAid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\BillingData::toArray
     *
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $billingData = new BillingData('1', '1');
        self::assertEquals('1', $billingData->getAid());
        self::assertEquals('1', $billingData->getEuid());
        self::assertEquals(['aid' => '1', 'euid' => '1'], $billingData->toArray());
        $billingData->setAid('2');
        $billingData->setEuid('2');
        self::assertEquals('2', $billingData->getAid());
        self::assertEquals('2', $billingData->getEuid());
        self::assertEquals(['aid' => '2', 'euid' => '2'], $billingData->toArray());
    }

}
