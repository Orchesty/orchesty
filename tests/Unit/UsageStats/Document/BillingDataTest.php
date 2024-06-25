<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UsageStats\Document;

use Exception;
use Hanaboso\PipesFramework\UsageStats\Document\AppInstallBillingData;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class BillingDataTest
 *
 * @package PipesFrameworkTests\Unit\UsageStats\Document
 */
#[CoversClass(AppInstallBillingData::class)]
final class BillingDataTest extends DatabaseTestCaseAbstract
{

    /**
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
