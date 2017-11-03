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
 * Class HubspotGetContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotGetContactConnector implements ConnectorInterface
{

    private const CONTACT_URL = '/contacts/v1/contact/vid/%s/profile';

    /**
     * @var HubspotSystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * HubspotSyncConnector constructor.
     *
     * @param HubspotSystem        $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(HubspotSystem $system, DocumentManager $dm, CurlManagerInterface $curlManager)
    {
        $this->system                  = $system;
        $this->curlManager             = $curlManager;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hubspot-get-contact-connector';
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
            'Hubspot has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH
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
        $arr = json_decode($dto->getData(), TRUE);

        $message = '';
        if (!is_array($arr) || empty($arr)) {
            $message = 'Empty data or bad format.';
        } elseif (!array_key_exists(HubspotSystem::SUBSCRIPTION_TYPE_KEY, $arr)) {
            $message = 'Missing "subscriptionType" in data.';
        } elseif (!array_key_exists(HubspotSystem::OBJECT_ID_KEY, $arr)) {
            $message = 'Missing "objectId" in data.';
        }

        if ($message != '') {
            throw new CleverConnectorsException($message, CleverConnectorsException::MISSING_DATA);
        }

        $allowedTypes = [
            HubspotSystem::SUBSCRIPTION_TYPE_CREATE,
            HubspotSystem::SUBSCRIPTION_TYPE_UPDATE,
        ];

        if (in_array($arr[HubspotSystem::SUBSCRIPTION_TYPE_KEY], $allowedTypes)) {
            $dto = $this->getContactProfile($dto, $arr);
        } elseif ($arr[HubspotSystem::SUBSCRIPTION_TYPE_KEY] == HubspotSystem::SUBSCRIPTION_TYPE_DELETE) {
            $dto = $this->setHeadersToStop($dto);
        } else {
            throw new CleverConnectorsException(
                sprintf('Unknown subscription type "%s"', $arr[HubspotSystem::SUBSCRIPTION_TYPE_KEY]),
                CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE
            );
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     * @param array      $body
     *
     * @return ProcessDto
     */
    private function getContactProfile(ProcessDto $dto, array $body): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $query = sprintf(self::CONTACT_URL, $body[HubspotSystem::OBJECT_ID_KEY]);
        $requestDto->setUri(new Uri(sprintf('%s%s', $requestDto->getUri(TRUE), $query)));

        $response = $this->curlManager->send($requestDto);

        $responseBody                                       = json_decode($response->getBody(), TRUE);
        $responseBody[HubspotSystem::SUBSCRIPTION_TYPE_KEY] = $body[HubspotSystem::SUBSCRIPTION_TYPE_KEY];

        $dto->setData(json_encode($responseBody));

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function setHeadersToStop(ProcessDto $dto): ProcessDto
    {
        $headers       = $dto->getHeaders();
        $key           = CMHeaders::createKey(CMHeaders::RESULT_CODE);
        $headers[$key] = 1003;
        $dto->setHeaders($headers);

        return $dto;
    }

}