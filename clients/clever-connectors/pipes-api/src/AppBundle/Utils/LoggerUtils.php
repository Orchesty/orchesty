<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;

/**
 * Class LoggerUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class LoggerUtils
{

    private const GUID        = 'guid';
    private const TOKEN       = 'token';
    private const SYSTEM_KEY  = 'system_key';
    private const SYSTEM_NAME = 'system_name';

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     *
     * @return array
     */
    public static function getMessage(SystemInterface $system, SystemInstall $systemInstall): array
    {
        return [
            self::GUID        => $systemInstall->getUser(),
            self::TOKEN       => $systemInstall->getToken(),
            self::SYSTEM_KEY  => $system->getKey(),
            self::SYSTEM_NAME => $system->getName(),
        ];
    }

}