<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ConnectorAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Connector
 */
abstract class ConnectorAbstract implements ConnectorInterface
{

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
     * @param int        $statusCode
     * @param ProcessDto $dto
     *
     * @return bool
     * @throws PipesFrameworkException
     */
    public function evaluateStatusCode(int $statusCode, ProcessDto $dto): bool
    {
        if (in_array($statusCode, $this->okStatuses)) {
            return TRUE;
        } elseif (in_array($statusCode, $this->badStatuses)) {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);

            return FALSE;
        } else {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);

            return FALSE;
        }
    }

}
