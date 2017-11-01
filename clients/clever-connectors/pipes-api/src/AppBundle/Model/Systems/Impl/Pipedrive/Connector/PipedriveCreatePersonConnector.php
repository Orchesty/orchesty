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
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class PipedriveCreatePersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveCreatePersonConnector implements ConnectorInterface
{

    private const SUB_URL = '/persons?api_token=%s';

    /**
     * @var PipedriveSystem
     */
    protected $system;

    /**
     * @var CurlManagerInterface
     */
    protected $curl;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

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
        return 'pipedrive-create-person-connector';
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

        $requestDto = $this->system->getRequestDto($systemInstall, 'POST');

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setBody($dto->getData())
            ->setUri(new Uri(sprintf(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL,
                $sett[PipedriveSystem::API_TOKEN])));

        $res = $this->curl->send($requestDto);

        if ($res->getStatusCode() != 201) {
            throw new CleverConnectorsException('Failed to create new contact in Pipedrive.',
                CleverConnectorsException::REQUEST_FAILED);
        }

        return $dto->setData($res->getBody());
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