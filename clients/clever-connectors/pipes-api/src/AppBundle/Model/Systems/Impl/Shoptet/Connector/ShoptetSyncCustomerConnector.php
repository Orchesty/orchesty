<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\ShoptetSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ShoptetSyncCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Connector
 */
class ShoptetSyncCustomerConnector implements ConnectorInterface, BatchInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var ShoptetSystem
     */
    private $shoptetSystem;

    /**
     * @var CurlSenderFactory
     */
    private $curlSenderFactory;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * ShoptetSyncCustomerConnector constructor.
     *
     * @param ShoptetSystem     $shoptetSystem
     * @param CurlSenderFactory $curlSenderFactory
     * @param DocumentManager   $documentManager
     */
    public function __construct(
        ShoptetSystem $shoptetSystem,
        CurlSenderFactory $curlSenderFactory,
        DocumentManager $documentManager
    )
    {
        $this->shoptetSystem           = $shoptetSystem;
        $this->curlSenderFactory       = $curlSenderFactory;
        $this->systemInstallRepository = $documentManager->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->curlSenderFactory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->shoptetSystem->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $promise = $this->fetchData($sender, $requestDto)
            ->then(
                function (ResponseInterface $response): SuccessMessage {
                    return $this->createSuccessMessage($response);
                },
                function (ResponseException $e) use ($systemInstall): void {
                    $this->logError($e->getResponse()->getStatusCode(), $this->shoptetSystem, $systemInstall);
                    throw $e;
                }
            )->then($callbackItem);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-sync-customer-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shoptet has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shoptet has not implemented "processAction" function.');
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $request
     *
     * @return PromiseInterface
     */
    protected function fetchData(CurlSender $sender, RequestDto $request): PromiseInterface
    {
        return $sender->send($request);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return SuccessMessage
     */
    protected function createSuccessMessage(ResponseInterface $response): SuccessMessage
    {
        // receives xml in body
        $successMessage = new SuccessMessage(1);
        $successMessage->setData($response->getBody()->getContents());

        return $successMessage;
    }

}