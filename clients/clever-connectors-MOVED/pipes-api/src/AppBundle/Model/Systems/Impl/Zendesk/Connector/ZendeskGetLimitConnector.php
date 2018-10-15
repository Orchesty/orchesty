<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\ZendeskSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class ZendeskGetLimitConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
class ZendeskGetLimitConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL = '%s/api/v2/account/settings.json';

    /**
     * @var ZendeskSystem
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
     * ZendeskGetLimitConnector constructor.
     *
     * @param ZendeskSystem        $system
     * @param DocumentManager      $documentManager
     * @param CurlManagerInterface $manager
     */
    public function __construct(
        ZendeskSystem $system,
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
        return 'zendesk-get-limit-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Zendesk has not implemented "processEvent" function.');
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

        $this->system->saveLimit($systemInstall, $response->getHeaders());
        $this->dm->flush();

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequestDto
     * @throws SystemException
     * @throws CurlException
     */
    private function prepareRequestDto(SystemInstall $systemInstall): RequestDto
    {
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setUri(new Uri(sprintf(self::URL, $requestDto->getUri())));

        return $requestDto;
    }

}