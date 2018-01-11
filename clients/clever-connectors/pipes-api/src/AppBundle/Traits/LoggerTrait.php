<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Trait LoggerTrait
 *
 * @package CleverConnectors\AppBundle\Traits
 */
trait LoggerTrait
{

    /**
     * @var string
     */
    private static $guid = 'guid';

    /**
     * @var string
     */
    private static $token = 'token';

    /**
     * @var string
     */
    private static $systemKey = 'system_key';

    /**
     * @var string
     */
    private static $systemName = 'system_name';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param int             $status
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     */
    protected function logError(int $status, SystemInterface $system, SystemInstall $systemInstall): void
    {
        $msg = self::getMessage($system, $systemInstall);

        switch ($status) {
            case 400:
            case 404:
            case 409:
            case 422:
                $this->logger->info(NotificationTypeEnum::DATA_ERROR, $msg);
                break;
            case 401:
                $this->logger->info(NotificationTypeEnum::ACCESS_EXPIRATION, $msg);
                break;
            case 429:
                $this->logger->info(NotificationTypeEnum::SERVICE_UNAVAILABLE, $msg);
                break;
            default:
                $this->logger->info(NotificationTypeEnum::SERVICE_UNAVAILABLE, $msg);
        }
    }

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     *
     * @return array
     */
    private static function getMessage(SystemInterface $system, SystemInstall $systemInstall): array
    {
        return [
            self::$guid       => $systemInstall->getUser(),
            self::$token      => $systemInstall->getToken(),
            self::$systemKey  => $system->getKey(),
            self::$systemName => $system->getName(),
        ];
    }

}