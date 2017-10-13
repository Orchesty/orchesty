<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.10.17
 * Time: 17:36
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CronUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class ShopifySyncCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifySyncCustomerConnector implements BatchInterface, ConnectorInterface
{

    private const COUNT_URL     = 'admin/customers/count.json';
    private const CUSTOMERS_URL = 'admin/customers.json?limit=50&page=';

    /**
     * @var ShopifySystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * ShopifySyncConnector constructor.
     *
     * @param ShopifySystem     $system
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     */
    public function __construct(ShopifySystem $system, DocumentManager $dm, CurlSenderFactory $factory)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->factory                 = $factory;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shopify has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
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
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');

        $promise = $this->fetchData($sender, $this->createCountRequest($requestDto))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($sender, $callbackItem, $requestDto) {
                    return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto));
                }
            );

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param RequestDto $dto
     *
     * @return RequestInterface
     */
    private function createCountRequest(RequestDto $dto): RequestInterface
    {
        return new Request('GET', sprintf('%s%s', $dto->getUri(TRUE), self::COUNT_URL), $dto->getHeaders());
    }

    /**
     * @param CurlSender       $sender
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    protected function fetchData(CurlSender $sender, RequestInterface $request): PromiseInterface
    {
        return $sender->send($request);
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
     * @param int        $total
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $requestDto
     *
     * @return array
     */
    private function doPageLoop(int $total, CurlSender $sender, callable $callbackItem, RequestDto $requestDto): array
    {
        $requests = [];
        for ($i = 1; $i <= $total; $i++) {
            $requests[] = $this
                ->fetchData($sender, $this->createCustomerRequest($i, $requestDto))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
                    })
                ->then($callbackItem);
        }

        return $requests;
    }

    /**
     * @param int        $page
     * @param RequestDto $dto
     *
     * @return RequestInterface
     */
    private function createCustomerRequest(int $page, RequestDto $dto): RequestInterface
    {
        return new Request(
            'GET',
            sprintf('%s%s%s', $dto->getUri(TRUE), self::CUSTOMERS_URL, $page),
            $dto->getHeaders()
        );
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