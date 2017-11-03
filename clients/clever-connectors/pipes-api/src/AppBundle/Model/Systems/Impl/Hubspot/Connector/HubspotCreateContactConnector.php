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
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class HubspotCreateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotCreateContactConnector implements ConnectorInterface
{

    private const SUB_URL = '/contacts/v1/contact';

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
        return 'hubspot-create-contact-connector';
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
            'ProcessEvent is not implemented, Hubspot createContactConnector.',
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
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $uri           = new Uri(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL);

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri($uri)
            ->setBody($dto->getData());

        $res = $this->curl->send($requestDto);

        if ($res->getStatusCode() === 409) {
            throw new CleverConnectorsException(
                'Contact already exists, Hubspot createContactConnector.',
                CleverConnectorsException::REQUEST_FAILED
            );
        } elseif ($res->getStatusCode() !== 200) {
            throw new CleverConnectorsException(
                'Failed to create new contact / email already taken, Hubspot createContactConnector.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }

        $tmp                     = json_decode($res->getBody(), TRUE);
        $tmp['subscriptionType'] = 'contact.creation';

        return $dto->setData(json_encode($tmp));
    }

}