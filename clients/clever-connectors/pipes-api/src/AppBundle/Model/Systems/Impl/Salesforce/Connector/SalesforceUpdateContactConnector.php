<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class SalesforceUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
class SalesforceUpdateContactConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL = '%s/services/data/v40.0/sobjects/Contact/id/%s';

    /**
     * @var SalesforceSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $manager;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * SalesforceUpdateContactConnector constructor.
     *
     * @param SalesforceSystem     $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $manager
     */
    public function __construct(SalesforceSystem $system, DocumentManager $dm, CurlManagerInterface $manager)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->manager                 = $manager;
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'salesforce-update-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field _foreign_id',
                CleverConnectorsException::MISSING_DATA
            );
        }

        /** @var string $eventType */
        $eventType     = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders());
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_PATCH);
        $requestDto
            ->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $data[CleverFieldsEnum::FOREIGN_ID])))
            ->setBody(Json::encode([sprintf('%s__c', CleverCustomKeysEnum::getFromType($eventType)) => 1]))
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        return $dto->setData($response->getBody());
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
            'Salesforce has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /** @param CurlException|ResponseException $e
     *
     * @return bool
     */
    protected function limitReached($e): bool
    {
        return Strings::contains($e->getResponse()->getBody()->getContents(), 'REQUEST_LIMIT_EXCEEDED');
    }

}