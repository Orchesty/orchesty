<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Tests\KernelTestCaseAbstract;

/**
 * Class TopologyNameUtilsTest
 *
 * @package Tests\Unit\AppBundle\Utils
 */
class TopologyNameUtilsTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public static function testGetSyncName(): void
    {
        self::assertEquals('systemkey-sync-subscribers',
            TopologyNameUtils::getSyncName((new SystemInstall())->setSystem('systemkey')));
    }

    /**
     *
     */
    public static function testGetEventName(): void
    {
        $systemInstall = (new SystemInstall())->setSystem('syskey');
        self::assertEquals('syskey-' . SystemInstall::EVENT_HARD_BOUNCE . '-event',
            TopologyNameUtils::getEventName($systemInstall, SystemInstall::EVENT_HARD_BOUNCE));
        self::assertEquals('syskey-' . SystemInstall::EVENT_CREATE . '-event',
            TopologyNameUtils::getEventName($systemInstall, SystemInstall::EVENT_CREATE));
    }

    /**
     *
     */
    public static function testGetCustomEventName(): void
    {
        $systemInstall = (new SystemInstall())->setSystem('syss')->setUser('us');

        self::assertEquals('us-syss-' . SystemInstall::EVENT_HARD_BOUNCE . '-event',
            TopologyNameUtils::getCustomEventName($systemInstall, SystemInstall::EVENT_HARD_BOUNCE));
        self::assertEquals('us-syss-' . SystemInstall::EVENT_CREATE . '-event',
            TopologyNameUtils::getCustomEventName($systemInstall, SystemInstall::EVENT_CREATE));
    }

    /**
     *
     */
    public static function testGetCMEventName(): void
    {
        self::assertEquals('save-cmevents', TopologyNameUtils::getCMEventName());
    }

    /**
     *
     */
    public static function testGetSystemCMEventName(): void
    {
        $systemInstall = (new SystemInstall())->setSystem('syss');

        self::assertEquals('syss-save-cmevents', TopologyNameUtils::getSystemCMEventName($systemInstall));
    }

}