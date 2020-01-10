<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\Json;
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
     * @var mixed[]
     */
    protected $okStatuses = [
        200,
        201,
    ];

    /**
     * @var mixed[]
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
     * @return mixed[]
     */
    protected function getJsonContent(ProcessDto $dto): array
    {
        return Json::decode($dto->getData());
    }

    /**
     * @param ProcessDto $dto
     * @param mixed[]    $content
     *
     * @return ProcessDto
     */
    protected function setJsonContent(ProcessDto $dto, array $content): ProcessDto
    {
        return $dto->setData(Json::encode($content));
    }

}
