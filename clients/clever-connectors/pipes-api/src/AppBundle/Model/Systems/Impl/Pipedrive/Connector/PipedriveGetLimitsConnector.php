<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class PipedriveGetLimitsConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveGetLimitsConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const LIMIT_URL = 'permissionSets?api_token=';

    /**
     * @var PipedriveSystem
     */
    private $system;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * PipedriveGetLimitsConnector constructor.
     *
     * @param PipedriveSystem      $system
     * @param DocumentManager      $documentManager
     * @param CurlManagerInterface $curl
     */
    function __construct(
        PipedriveSystem $system,
        DocumentManager $documentManager,
        CurlManagerInterface $curl
    )
    {
        $this->system                  = $system;
        $this->curl                    = $curl;
        $this->dm                      = $documentManager;
        $this->systemInstallRepository = $documentManager->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipedrive-get-limit-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'PipeDrive get limit connector not implements process event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req           = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);

        $uri = new Uri(sprintf('%s%s%s', $req->getUri(TRUE), self::LIMIT_URL,
            $systemInstall->getSettings()[PipedriveSystem::API_TOKEN]));
        $req->setUri($uri);

        try {
            $res = $this->curl->send($req);
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        $this->system->saveLimit($systemInstall, $res->getHeaders());
        $this->dm->flush();

        return $dto;

    }

}