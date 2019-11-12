<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

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
        }

        $dto->setStopProcess(ProcessDto::STOP_AND_FAILED, $message);

        return FALSE;
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

    /**
     * @param ProcessDto $dto
     *
     * @return array
     */
    protected function getJsonContent(ProcessDto $dto): array
    {
        return json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param ProcessDto $dto
     * @param array      $content
     *
     * @return ProcessDto
     */
    protected function setJsonContent(ProcessDto $dto, array $content): ProcessDto
    {
        return $dto->setData(json_encode($content, JSON_THROW_ON_ERROR));
    }

}
