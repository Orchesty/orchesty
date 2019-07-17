<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubspotApplication;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class HubspotCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector
 */
class HubspotCreateContactConnector extends ConnectorAbstract
{

    /**
     * @var HubspotApplication
     */
    private $application;

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var ObjectRepository|ApplicationInstallRepository
     */
    private $repository;

    /**
     * HubspotCreateContactConnector constructor.
     *
     * @param HubspotApplication   $application
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(HubspotApplication $application, CurlManagerInterface $curlManager, DocumentManager $dm)
    {
        $this->application = $application;
        $this->curlManager = $curlManager;
        $this->repository  = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hubspot_create_contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $dto;
        throw new ConnectorException('ProcessEvent is not implemented',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);

        $return = $this->curlManager->send($this->application->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf('%s/contacts/v1/contact/', HubspotApplication::BASE_URL),
            $dto->getData(),
            ));

        $json = $return->getJsonBody();

        unset($json['correlationId']);
        unset($json['requestId']);

        $dto->setData((string) json_encode($json, JSON_THROW_ON_ERROR, 512));

        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto);

        return $dto;
    }

}
