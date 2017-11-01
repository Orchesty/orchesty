<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
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
 * Class HubspotUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotUpdateContactConnector implements ConnectorInterface
{

    private const SUB_URL = '/contacts/v1/contact/vid/%s/profile?hapikey=%s';

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var HubspotSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * HubspotCreateUserConnector constructor.
     *
     * @param HubspotSystem        $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     */
    function __construct(HubspotSystem $system, DocumentManager $dm, CurlManagerInterface $curl)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->curl                    = $curl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hubspot-update-contact-connector';
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
            'ProcessEvent is not implemented, Hubspot updateContactConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
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
        $data          = json_decode($dto->getData(), TRUE);

        $requestDto = $this->system->getRequestDto($systemInstall, 'POST');
        $query      = sprintf(self::SUB_URL, $data['id'], HubspotSystem::HAPI_KEY);
        $uri        = new Uri(sprintf(rtrim($requestDto->getUri(TRUE), '/') . $query));

        $requestDto
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri($uri)
            ->setBody($data['body']);

        $res = $this->curl->send($requestDto);

        if ($res->getStatusCode() === 404) {
            throw new CleverConnectorsException(
                sprintf('User with given id [%s] does not exist, Hubspot updateContactConnector.', $data['id']),
                CleverConnectorsException::REQUEST_FAILED
            );
        } else if ($res->getStatusCode() === 400) {
            throw new CleverConnectorsException(
                'There is a problem with the data in the request body, Hubspot updateContactConnector.',
                CleverConnectorsException::MISSING_DATA
            );
        } else if ($res->getStatusCode() !== 204) {
            throw new CleverConnectorsException(
                'Failed to update contact - unknown error, Hubspot updateContactConnector.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData($res->getBody());
    }

}