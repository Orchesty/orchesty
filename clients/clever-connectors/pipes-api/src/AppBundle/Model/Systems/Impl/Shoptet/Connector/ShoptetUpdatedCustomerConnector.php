<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Connector;

use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\ShoptetSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use CleverConnectors\AppBundle\Utils\Dto\Times;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ShoptetUpdatedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Connector
 */
class ShoptetUpdatedCustomerConnector implements ConnectorInterface, BatchInterface
{

    /**
     * @var ShoptetSystem
     */
    private $shoptetSystem;

    /**
     * @var CurlSenderFactory
     */
    private $curlSenderFactory;

    /**
     * @var LastSyncManager
     */
    private $lastSyncManager;

    /**
     * ShoptetSyncCustomerConnector constructor.
     *
     * @param ShoptetSystem     $shoptetSystem
     * @param CurlSenderFactory $curlSenderFactory
     * @param LastSyncManager   $lastSyncManager
     */
    public function __construct(
        ShoptetSystem $shoptetSystem,
        CurlSenderFactory $curlSenderFactory,
        LastSyncManager $lastSyncManager
    )
    {
        $this->shoptetSystem     = $shoptetSystem;
        $this->curlSenderFactory = $curlSenderFactory;
        $this->lastSyncManager   = $lastSyncManager;
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
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->shoptetSystem->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        $promise = $this->fetchData($sender, $requestDto)
            ->then(function (ResponseInterface $response) use ($times): SuccessMessage {
                return $this->createSuccessMessage($response, $times);
            })->then($callbackItem);

        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);

        return $promise;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-updated-customer-connector';
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
    protected function createSuccessMessage(ResponseInterface $response, Times $times): SuccessMessage
    {
        $xml = simplexml_load_string($response->getBody()->getContents());
        $xml->addChild('LAST_SYNC', strval($times->getStart()->getTimestamp()));

        // receives xml in body
        $successMessage = new SuccessMessage(1);
        $successMessage->setData($xml->asXML());

        var_dump($xml->children()->asXML());

        return $successMessage;
    }

}