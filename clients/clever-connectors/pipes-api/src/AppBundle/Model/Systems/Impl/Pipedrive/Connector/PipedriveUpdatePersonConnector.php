<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class PipedriveUpdatePersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveUpdatePersonConnector implements ConnectorInterface
{

    private const SUB_URL = '/persons/%s?api_token=%s';

    /**
     * @var PipedriveSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * PipedriveCreatePersonConnector constructor.
     *
     * @param PipedriveSystem      $system
     * @param CurlManagerInterface $curl
     * @param DocumentManager      $dm
     */
    function __construct(PipedriveSystem $system, CurlManagerInterface $curl, DocumentManager $dm)
    {
        $this->system                  = $system;
        $this->curl                    = $curl;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipesdrive-update-person-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett          = $systemInstall->getSettings();
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_PUT);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $data = json_decode($dto->getData(), TRUE);
        $uri  = new Uri(sprintf(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL,
            $data['id'],
            $sett[PipedriveSystem::API_TOKEN]
        ));

        $requestDto->setBody($data['body'])
            ->setUri($uri);

        $res = $this->curl->send($requestDto);

        if ($res->getStatusCode() != 200) {
            throw new CleverConnectorsException('Failed to update contact / missing field, PipeDrive.',
                CleverConnectorsException::REQUEST_FAILED);
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Pipedrive has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}