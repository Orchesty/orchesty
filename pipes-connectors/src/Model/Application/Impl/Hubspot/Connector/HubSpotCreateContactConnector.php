<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubSpotApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class HubSpotCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector
 */
final class HubSpotCreateContactConnector extends ConnectorAbstract implements LoggerAwareInterface
{

    /**
     * @var CurlManagerInterface
     */
    private CurlManagerInterface $curlManager;

    /**
     * @var ApplicationInstallRepository&ObjectRepository<ApplicationInstall>
     */
    private ApplicationInstallRepository $repository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * HubSpotCreateContactConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(CurlManagerInterface $curlManager, DocumentManager $dm)
    {
        $this->curlManager = $curlManager;
        $this->repository  = $dm->getRepository(ApplicationInstall::class);
        $this->logger      = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hub-spot.create-contact';
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
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
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);
        $body               = $this->getJsonContent($dto);

        try {
            $response = $this->curlManager->send(
                $this->getApplication()->getRequestDto(
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/contacts/v1/contact/', HubSpotApplication::BASE_URL),
                    Json::encode($body)
                )->setDebugInfo($dto)
            );
            $message  = $response->getJsonBody()['validationResults'][0]['message'] ?? NULL;
            $this->evaluateStatusCode($response->getStatusCode(), $dto, $message);

            if ($response->getStatusCode() === 409) {
                $parsed = $response->getJsonBody();
                $this->logger->error(
                    sprintf('Contact "%s" already exist.', $parsed['identityProfile']['identity'][0]['value'] ?? ''),
                    array_merge(
                        ['response' => $response->getBody(), PipesHeaders::debugInfo($dto->getHeaders())]
                    )
                );
            }

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        }

        return $dto;
    }

}
