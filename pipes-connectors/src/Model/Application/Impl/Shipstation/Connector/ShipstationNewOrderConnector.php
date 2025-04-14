<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class ShipstationNewOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector
 */
final class ShipstationNewOrderConnector extends ConnectorAbstract
{

    public const string NAME = 'shipstation_new_order';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws CurlException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        $url = $dto->getJsonData()['resource_url'] ?? NULL;
        if (!$url) {
            $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, 'Resource url not set');

            return $dto;
        }

        $return = $this->getSender()->send(
            $this->getApplication()->getRequestDto(
                $dto,
                $applicationInstall,
                CurlManager::METHOD_GET,
                $url,
            ),
        );

        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

}
