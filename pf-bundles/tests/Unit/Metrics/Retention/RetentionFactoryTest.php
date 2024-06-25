<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Metrics\Retention;

use Exception;
use Hanaboso\PipesFramework\Metrics\Retention\RetentionFactory;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class RetentionFactoryTest
 *
 * @package PipesFrameworkTests\Unit\Metrics\Retention
 */
#[CoversClass(RetentionFactory::class)]
final class RetentionFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetRetention(): void
    {
        $retention = RetentionFactory::getRetentionInSeconds(
            DateTimeUtils::getUtcDateTime('10.1.2020'),
            DateTimeUtils::getUtcDateTime('11.1.2020'),
        );
        self::assertEquals(1_800, $retention);

        $retention = RetentionFactory::getRetentionInSeconds(
            DateTimeUtils::getUtcDateTime('10.1.2020'),
            DateTimeUtils::getUtcDateTime('12.1.2020'),
        );
        self::assertEquals(1_4400, $retention);

        $retention = RetentionFactory::getRetentionInSeconds(
            DateTimeUtils::getUtcDateTime('now'),
            DateTimeUtils::getUtcDateTime('now + 10min'),
        );
        self::assertEquals(60, $retention);

        $retention = RetentionFactory::getRetentionInSeconds(
            DateTimeUtils::getUtcDateTime('now'),
            DateTimeUtils::getUtcDateTime('now + 60second'),
        );
        self::assertEquals(5, $retention);
    }

}
