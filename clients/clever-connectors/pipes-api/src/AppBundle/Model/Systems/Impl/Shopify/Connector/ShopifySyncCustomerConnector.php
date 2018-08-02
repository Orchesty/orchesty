<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.10.17
 * Time: 17:36
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class ShopifySyncCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifySyncCustomerConnector implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const PER_PAGE      = 50;
    private const COUNT_URL     = 'admin/customers/count.json';
    private const CUSTOMERS_URL = 'admin/customers.json?limit=' . self::PER_PAGE . '&page=';

    /**
     * @var ShopifySystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * @var ProgressCounterService
     */
    private $counterService;

    /**
     * ShopifySyncConnector constructor.
     *
     * @param ShopifySystem          $system
     * @param DocumentManager        $dm
     * @param CurlSenderFactory      $factory
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        ShopifySystem $system,
        DocumentManager $dm,
        CurlSenderFactory $factory,
        ProgressCounterService $counterService
    )
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->factory                 = $factory;
        $this->counterService          = $counterService;
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shopify has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shopify has not implemented "processAction" function.');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-sync-customer-connector';
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     * @throws CurlException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $url       = new Uri(sprintf('%s%s', $requestDto->getUri(TRUE), self::COUNT_URL));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';

        $promise = $this->fetchData($sender, RequestDto::from($requestDto, $url))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                },
                function (ResponseException $e) use ($systemInstall, $callbackItem) {
                    $success = $this->batchConnectorError($e, $this->system, $systemInstall, 1);

                    return $callbackItem($success);
                }
            )->then(
                function (int $total) use ($sender, $callbackItem, $requestDto, $processId, $systemInstall) {
                    $this->counterService->setTotal($processId, $total * self::PER_PAGE);

                    return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto, $systemInstall));
                }
            );

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $dto
     *
     * @return PromiseInterface
     */
    protected function fetchData(CurlSender $sender, RequestDto $dto): PromiseInterface
    {
        return $sender->send($dto);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return int
     * @throws SystemException
     */
    protected function getTotalPages(ResponseInterface $response): int
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('count', $data)) {
            throw new SystemException('Shopify response has no "count" field!', SystemException::MISSING_RESPONSE_DATA);
        }

        $total = (int) ceil($data['count'] / 50);
        unset($data);

        return $total;
    }

    /**
     * @param int           $total
     * @param CurlSender    $sender
     * @param callable      $callbackItem
     * @param RequestDto    $dto
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CurlException
     */
    private function doPageLoop(
        int $total,
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $dto,
        SystemInstall $systemInstall
    ): array
    {
        $requests = [];
        for ($i = 1; $i <= $total; $i++) {
            $url        = new Uri(sprintf('%s%s%s', $dto->getUri(TRUE), self::CUSTOMERS_URL, $i));
            $requests[] = $this
                ->fetchData($sender, RequestDto::from($dto, $url))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
                    },
                    function (ResponseException $e) use ($systemInstall, $i): SuccessMessage {
                        return $this->batchConnectorError($e, $this->system, $systemInstall, $i + 1);
                    }
                )
                ->then($callbackItem, $callbackItem);
        }

        return $requests;
    }

    /**
     * @param ResponseInterface $response
     * @param int               $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    private function createSuccessMessage(ResponseInterface $response, int $i): SuccessMessage
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($data) && array_key_exists('customers', $data)) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['customers']));
            unset($data);

            return $successMessage;
        }
        throw new SystemException(
            'Shopify Error: Key customers not found in response.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}