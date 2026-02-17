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
 * Class HttpStatus400Connector
 *
 * @package Demo\HttpStatus\Connector
 */
final class HttpStatus400Connector extends ConnectorAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return sprintf('%s-400-connector', HttpStatusApplication::NAME);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws ConnectorException
     * @throws PipesFrameworkException
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws GuzzleException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $response = $this->getSender()->send(
            $this->getApplication()->getRequestDto(
                $dto,
                $this->getApplicationInstallFromProcess($dto),
                CurlManager::METHOD_GET,
                '400',
            ),
        );

        return $dto->setStopProcess(
            ProcessDto::STOP_AND_FAILED,
            sprintf('status: %s, body: %s', $response->getStatusCode(), $response->getBody()),
        );
    }

}
