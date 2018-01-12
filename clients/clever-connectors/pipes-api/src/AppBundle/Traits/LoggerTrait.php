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
    public static function getMessage(SystemInterface $system, SystemInstall $systemInstall): array
    {
        return [
            self::$guid       => $systemInstall->getUser(),
            self::$token      => $systemInstall->getToken(),
            self::$systemKey  => $system->getKey(),
            self::$systemName => $system->getName(),
        ];
    }

}