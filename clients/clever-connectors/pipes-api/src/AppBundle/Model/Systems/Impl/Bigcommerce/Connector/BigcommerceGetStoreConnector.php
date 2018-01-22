<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class BigcommerceGetStoreConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceGetStoreConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const STORE_URL = 'store';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var BigcommerceSystem
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
     * BigcommerceGetStoreConnector constructor.
     *
     * @param BigcommerceSystem    $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $manager
     */
    public function __construct(BigcommerceSystem $system, DocumentManager $dm, CurlManagerInterface $manager)
    {
        $this->system                  = $system;
        $this->dm                      = $dm;
        $this->manager                 = $manager;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-get-store-connector';
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
            'Bigcommerce has no support for action!',
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
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        try {
            $response = $this->manager->send($requestDto->setUri(
                new Uri(sprintf($requestDto->getUri(TRUE) . self::STORE_URL))
            ));
            $this->system->saveLimit($systemInstall, Json::decode($response->getBody(), TRUE));
            $this->dm->flush();
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        return $dto;
    }

    /**
     * @param CurlException|ResponseException $e
     *
     * @return bool
     */
    protected function limitReached($e): bool
    {
        return $e->getResponse()->getStatusCode() === 509;
    }

}