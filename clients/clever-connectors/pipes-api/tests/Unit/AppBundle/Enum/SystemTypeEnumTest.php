<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Enum;

use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use Tests\KernelTestCaseAbstract;

/**
 * Class SystemTypeEnumTest
 *
 * @package Tests\Unit\AppBundle\Enum
 */
final class SystemTypeEnumTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testIsWebhook(): void
    {
        self::assertTrue(SystemTypeEnum::isWebhook(SystemTypeEnum::WEBHOOK));
        self::assertTrue(SystemTypeEnum::isWebhook(SystemTypeEnum::UI_WEBHOOK));
        self::assertFalse(SystemTypeEnum::isWebhook(SystemTypeEnum::CRON));
    }

}