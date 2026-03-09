<?php declare(strict_types=1);

namespace Demo\HttpStatus\Connector;

use Demo\HttpStatus\HttpStatusApplication;
use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class HttpStatus401Connector
 *
 * @package Demo\HttpStatus\Connector
 */
final class HttpStatus401Connector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return sprintf('%s-401-connector', HttpStatusApplication::NAME);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws CurlException
     * @throws CustomNodeException
     * @throws GuzzleException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $response = $this->getSender()->send(
            $this->getApplication()->getRequestDto(
                $dto,
                $this->getApplicationInstallFromProcess($dto),
                CurlManager::METHOD_GET,
                '401',
            ),
        );

        return $dto->setStopProcess(
            ProcessDto::STOP_AND_FAILED,
            sprintf('status: %s, body: %s', $response->getStatusCode(), $response->getBody()),
        );
    }

}
