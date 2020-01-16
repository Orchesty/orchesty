<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubspotApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class HubspotCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnector extends ConnectorAbstract
{

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var ApplicationInstallRepository&ObjectRepository<ApplicationInstall>
     */
    private $repository;

    /**
     * HubspotCreateContactConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(CurlManagerInterface $curlManager, DocumentManager $dm)
    {
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

        throw new ConnectorException(
            'ProcessEvent is not implemented',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
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

        $return = $this->curlManager->send(
            $this->application->getRequestDto(
                $applicationInstall,
                CurlManager::METHOD_POST,
                sprintf('%s/contacts/v1/contact/', HubspotApplication::BASE_URL),
                $dto->getData()
            )
        );

        $json = $return->getJsonBody();

        unset($json['correlationId'], $json['requestId']);

        $message    = $json['validationResults'][0]['message'] ?? NULL;
        $statusCode = $return->getStatusCode();
        $this->evaluateStatusCode($statusCode, $dto, $message);

        return $this->setJsonContent($dto, $json);
    }

}
