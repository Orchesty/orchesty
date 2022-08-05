<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Trait ConnectorTrait
 *
 * @package Hanaboso\PipesPhpSdk\Connector
 */
trait ConnectorTrait
{

    protected ?CurlManagerInterface $sender;

    /**
     * @var mixed[]
     */
    protected array $okStatuses = [
        200,
        201,
    ];

    /**
     * @param int                $statusCode
     * @param ProcessDtoAbstract $dto
     * @param string|null        $message
     *
     * @return bool
     * @throws PipesFrameworkException
     */
    public function evaluateStatusCode(int $statusCode, ProcessDtoAbstract $dto, ?string $message = NULL): bool
    {
        if (in_array($statusCode, $this->okStatuses, TRUE)) {
            return TRUE;
        }

        if (!$message) {
            $message = sprintf(
                'Returned StatusCode [%d] is not in allowed statusCodes [%s]',
                $statusCode,
                implode(', ', $this->okStatuses),
            );
        }

        $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, $message);

        return FALSE;
    }

    /**
     * @param CurlManagerInterface $sender
     *
     * @return self
     */
    public function setSender(CurlManagerInterface $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return CurlManagerInterface
     * @throws ConnectorException
     */
    protected function getSender(): CurlManagerInterface
    {
        if ($this->sender) {
            return $this->sender;
        }

        throw new ConnectorException('CurlManager has not set.');
    }

}
