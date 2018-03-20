<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Clue\React\Buzz\Message\ResponseException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
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
    protected static $guid = 'guid';

    /**
     * @var string
     */
    protected static $token = 'token';

    /**
     * @var string
     */
    protected static $systemKey = 'system_key';

    /**
     * @var string
     */
    protected static $systemName = 'system_name';

    /**
     * @var string
     */
    protected static $notificationType = 'notification_type';

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
     * @param CurlException   $e
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param ProcessDto      $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    protected function connectorError(
        CurlException $e,
        SystemInterface $system,
        SystemInstall $systemInstall,
        ProcessDto $dto
    ): ProcessDto
    {
        if ($e->getResponse()) {
            if ($this->limitReached($e)) {
                return HeadersUtils::setLimitHeaderToDto($dto);
            }
            $this->logError($e->getResponse()->getStatusCode(), $system, $systemInstall);
        }

        throw $e;
    }

    /**
     * @param ResponseException $e
     * @param SystemInterface   $system
     * @param SystemInstall     $systemInstall
     * @param int               $i
     *
     * @return SuccessMessage
     */
    protected function batchConnectorError(
        ResponseException $e,
        SystemInterface $system,
        SystemInstall $systemInstall,
        int $i
    ): SuccessMessage
    {
        if ($e->getResponse()) {
            if ($this->limitReached($e)) {
                $successMessage = new SuccessMessage($i);

                return HeadersUtils::setLimitHeaderToMessage($successMessage);
            }
            $this->logError($e->getResponse()->getStatusCode(), $system, $systemInstall);
        }

        throw $e;
    }

    /**
     * @param CurlException|ResponseException $e
     *
     * @return bool
     */
    protected function limitReached($e): bool
    {
        // Override in connector for system specifics.
        return $e->getResponse()->getStatusCode() === 429;
    }

    /**
     * @param int             $status
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     */
    protected function logError(int $status, SystemInterface $system, SystemInstall $systemInstall): void
    {
        switch ($status) {
            case 400:
            case 404:
            case 409:
            case 422:
                $this->logger->error(
                    NotificationTypeEnum::DATA_ERROR,
                    self::getMessage(NotificationTypeEnum::DATA_ERROR, $system, $systemInstall)
                );
                break;
            case 401:
                $this->logger->error(
                    NotificationTypeEnum::ACCESS_EXPIRATION,
                    self::getMessage(NotificationTypeEnum::ACCESS_EXPIRATION, $system, $systemInstall)
                );
                break;
            case 429:
                $this->logger->error(
                    NotificationTypeEnum::SERVICE_UNAVAILABLE,
                    self::getMessage(NotificationTypeEnum::SERVICE_UNAVAILABLE, $system, $systemInstall)
                );
                break;
            default:
                $this->logger->error(
                    NotificationTypeEnum::SERVICE_UNAVAILABLE,
                    self::getMessage(NotificationTypeEnum::SERVICE_UNAVAILABLE, $system, $systemInstall)
                );
        }
    }

    /**
     * @param string          $type
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     *
     * @return array
     */
    public static function getMessage(string $type, SystemInterface $system, SystemInstall $systemInstall): array
    {
        return [
            self::$notificationType => $type,
            self::$guid             => $systemInstall->getUser(),
            self::$token            => $systemInstall->getToken(),
            self::$systemKey        => $system->getKey(),
            self::$systemName       => $system->getName(),
        ];
    }

}