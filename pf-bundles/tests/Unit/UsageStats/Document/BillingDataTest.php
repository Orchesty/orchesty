<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Document;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class BillingDataTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Document
 */
final class BillingDataTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData::setEuid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData::setAid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData::getEuid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData::getAid
     * @covers \Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData::toArray
     *
     * @throws Exception
     */
    public function testBillingEvent(): void
    {
        $appInstallBillingData = new AppInstallBillingData('1', '1');
        self::assertEquals('1', $appInstallBillingData->getAid());
        self::assertEquals('1', $appInstallBillingData->getEuid());
        self::assertEquals(['aid' => '1', 'euid' => '1'], $appInstallBillingData->toArray());
        $appInstallBillingData->setAid('2');
        $appInstallBillingData->setEuid('2');
        self::assertEquals('2', $appInstallBillingData->getAid());
        self::assertEquals('2', $appInstallBillingData->getEuid());
        self::assertEquals(['aid' => '2', 'euid' => '2'], $appInstallBillingData->toArray());
    }

}
