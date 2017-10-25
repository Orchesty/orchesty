<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 17:20
 */

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use LogicException;

/**
 * Class TopologyNameUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class TopologyNameUtils
{

    /**
     * @param SystemInstall $systemInstall
     *
     * @return string
     */
    public static function getSyncName(SystemInstall $systemInstall): string
    {
        return sprintf('%s-sync-subscribers', $systemInstall->getSystem());
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $event
     *
     * @return string
     */
    public static function getEventName(SystemInstall $systemInstall, string $event): string
    {
        if (!SystemInstall::isEvent($event)) {
            throw new LogicException(sprintf('Event type ["%s"] is not valid.', $event));
        }

        return sprintf('%s-%s-event', $systemInstall->getSystem(), $event);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $event
     *
     * @return string
     */
    public static function getCustomEventName(SystemInstall $systemInstall, string $event): string
    {
        return sprintf('%s-%s', $systemInstall->getUser(), self::getEventName($systemInstall, $event));
    }

    /**
     * @return string
     */
    public static function getCMEventName(): string
    {
        return 'save-cmevents';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return string
     */
    public static function getSystemCMEventName(SystemInstall $systemInstall): string
    {
        return sprintf('%s-%s', $systemInstall->getSystem(), self::getCMEventName());
    }

}