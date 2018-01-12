<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Log\LoggerInterface;

/**
 * Trait ZohoLoggerTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Traits
 */
trait ZohoLoggerTrait
{

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
     * @param ProcessDto      $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    protected function connectorError(
        int $status,
        SystemInterface $system,
        SystemInstall $systemInstall,
        ProcessDto $dto
    ): ProcessDto
    {
        if ($status === ZohoSystem::LIMIT_STATUS_CODE) {
            return HeadersUtils::setLimitHeaderToDto($dto);
        }
        $this->logError($this->getStatus($status), $system, $systemInstall);

        throw new CleverConnectorsException(
            'Zoho connector request failed.',
            CleverConnectorsException::REQUEST_FAILED
        );
    }

    /**
     * @param int             $status
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param int             $i
     *
     * @return SuccessMessage
     * @throws CleverConnectorsException
     */
    protected function batchConnectorError(
        int $status,
        SystemInterface $system,
        SystemInstall $systemInstall,
        int $i
    ): SuccessMessage
    {
        if ($status === ZohoSystem::LIMIT_STATUS_CODE) {
            $successMessage = new SuccessMessage($i);

            return HeadersUtils::setLimitHeaderToMessage($successMessage);
        }
        $this->logError($this->getStatus($status), $system, $systemInstall);

        throw new CleverConnectorsException(
            'Zoho batch connector request failed.',
            CleverConnectorsException::REQUEST_FAILED
        );
    }

    /**
     * @param int             $status
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     *
     * @throws CleverConnectorsException
     */
    protected function logError(int $status, SystemInterface $system, SystemInstall $systemInstall): void
    {
        $msg = LoggerTrait::getMessage($system, $systemInstall);
        if ($status === 401) {
            $this->logger->info(NotificationTypeEnum::ACCESS_EXPIRATION, $msg);
        } else {
            $this->logger->info(NotificationTypeEnum::DATA_ERROR, $msg);
        }
    }

    /**
     * @param int $status
     *
     * @return int
     */
    private function getStatus(int $status): int
    {
        return in_array($status, ZohoSystem::AUTH_STATUS_CODES) ? 401 : 400;
    }

}