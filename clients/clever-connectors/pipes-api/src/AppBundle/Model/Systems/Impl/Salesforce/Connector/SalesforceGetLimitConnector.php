<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class SalesforceGetLimitConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
class SalesforceGetLimitConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL = '%s/services/data/v40.0/limits';

    /**
     * @var SalesforceSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $manager;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * SalesforceGetLimitConnector constructor.
     *
     * @param SalesforceSystem     $system
     * @param DocumentManager      $documentManager
     * @param CurlManagerInterface $manager
     */
    public function __construct(
        SalesforceSystem $system,
        DocumentManager $documentManager,
        CurlManagerInterface $manager
    )
    {
        $this->system                  = $system;
        $this->manager                 = $manager;
        $this->dm                      = $documentManager;
        $this->systemInstallRepository = $documentManager->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'salesforce-get-limit-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Salesforce has not implemented "processEvent" function.');
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
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->prepareRequestDto($systemInstall);

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        $body = Json::decode($response->getBody(), TRUE);
        $this->system->saveLimit($systemInstall, $body);
        $this->dm->flush();

        return $dto;
    }

    /**
     * @param CurlException|ResponseException $e
     *
     * @return bool
     */
    protected function limitReached($e): bool
    {
        return Strings::contains($e->getResponse()->getBody()->getContents(), 'REQUEST_LIMIT_EXCEEDED');
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequestDto
     * @throws SystemException
     */
    private function prepareRequestDto(SystemInstall $systemInstall): RequestDto
    {
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setUri(new Uri(sprintf(self::URL, $requestDto->getUri())));

        return $requestDto;
    }

}