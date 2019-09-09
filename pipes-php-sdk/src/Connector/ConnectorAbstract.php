<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;

/**
 * Class ConnectorAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Connector
 */
abstract class ConnectorAbstract implements ConnectorInterface
{

    /**
     * @var ApplicationInterface
     */
    protected $application;

    /**
     * @var array
     */
    protected $okStatuses = [
        200,
        201,
    ];

    /**
     * @var array
     */
    protected $badStatuses = [
        409,
        400,
    ];

    /**
     * @param int         $statusCode
     * @param ProcessDto  $dto
     * @param string|null $message
     *
     * @return bool
     * @throws PipesFrameworkException
     */
    public function evaluateStatusCode(int $statusCode, ProcessDto $dto, ?string $message = NULL): bool
    {
        if (in_array($statusCode, $this->okStatuses)) {
            return TRUE;
        } elseif (in_array($statusCode, $this->badStatuses)) {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED, $message);

            return FALSE;
        } else {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);

            return FALSE;
        }
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return ConnectorInterface
     */
    public function setApplication(ApplicationInterface $application): ConnectorInterface
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        /** @var ApplicationInterface|null $application */
        $application = $this->application;
        if ($application) {

            return $application->getKey();
        }

        return NULL;
    }

}
