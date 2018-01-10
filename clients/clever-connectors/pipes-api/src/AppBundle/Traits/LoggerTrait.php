<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Utils\LoggerUtils;
use Psr\Log\LoggerInterface;

/**
 * Trait LoggerTrait
 *
 * @package CleverConnectors\AppBundle\Traits
 */
trait LoggerTrait
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param int             $status
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     */
    protected function logError(int $status, SystemInterface $system, SystemInstall $systemInstall): void
    {
        switch ($status) {
            case 400:
                $this->logger->info(NotificationTypeEnum::DATA_ERROR,
                    LoggerUtils::getMessage($system, $systemInstall));
                break;
            case 401:
                $this->logger->info(NotificationTypeEnum::ACCESS_EXPIRATION,
                    LoggerUtils::getMessage($system, $systemInstall));
                break;
        }
    }

}