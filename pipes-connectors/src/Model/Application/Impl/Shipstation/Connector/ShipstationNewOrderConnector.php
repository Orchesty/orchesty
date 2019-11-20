<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class ShipstationNewOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector
 */
class ShipstationNewOrderConnector extends ConnectorAbstract
{

    /**
     * ShipstationNewOrderConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(
        CurlManagerInterface $curlManager,
        DocumentManager $dm
    )
    {
        $this->curlManager = $curlManager;
        $this->repository  = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shipstation_new_order';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws PipesFrameworkException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);

        $url = Json::decode($dto->getData())['resource_url'] ?? NULL;
        if (!$url) {
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED);

            return $dto;
        }

        $return = $this->curlManager->send(
            $this->application->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_GET,
                $url,
                NULL
            )
        );

        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);
        $dto->setData($return->getBody());

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(
            'ProcessAction is not implemented',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );

    }

}
