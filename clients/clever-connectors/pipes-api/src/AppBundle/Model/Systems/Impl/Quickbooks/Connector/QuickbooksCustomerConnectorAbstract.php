<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/23/17
 * Time: 3:21 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class QuickbooksCustomerConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
abstract class QuickbooksCustomerConnectorAbstract implements BatchInterface, ConnectorInterface
{

    protected const PAGE_LIMIT = 50;

    /**
     * @var QuickbooksSystem
     */
    private $system;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * @var LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * ShopifySyncConnector constructor.
     *
     * @param QuickbooksSystem  $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     */
    public function __construct(QuickbooksSystem $system, LastSyncManager $lastSyncManager, CurlSenderFactory $factory)
    {
        $this->system          = $system;
        $this->factory         = $factory;
        $this->lastSyncManager = $lastSyncManager;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Quickbooks has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Quickbooks has not implemented "processAction" function.');
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return string
     */
    abstract protected function getTotalQuery(SystemInstall $systemInstall, ProcessDto $dto): string;

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     * @param int           $start
     * @param int           $count
     *
     * @return string
     */
    abstract protected function getDataQuery(
        SystemInstall $systemInstall,
        ProcessDto $dto,
        int $start,
        int $count
    ): string;

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
        $systemInstall = $this->getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $url     = new Uri($requestDto->getUri(TRUE) . 'query?query=' . urlencode($this->getTotalQuery($systemInstall,
                $dto)));
        $promise = $this->fetchData($sender, RequestDto::from($requestDto, $url))->then(
            function (ResponseInterface $response): int {
                return $this->getTotalPages($response);
            }
        )->then(
            function (int $total) use ($systemInstall, $dto, $sender, $callbackItem, $requestDto) {
                return all($this->doPageLoop($systemInstall, $dto, $total, $sender, $callbackItem, $requestDto));
            }
        );

        return $promise;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @param ProcessDto    $dto
     *
     * @return void
     */
    abstract protected function afterFetch(SystemInstall $systemInstall, ProcessDto $dto): void;

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    abstract protected function getSystemInstall(ProcessDto $dto): SystemInstall;

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

        if (!is_array($data) || !array_key_exists('QueryResponse', $data) || !array_key_exists('totalCount',
                $data['QueryResponse'])) {
            throw new SystemException('Quickbooks response has no "QueryResponse -> totalCount" field!',
                SystemException::MISSING_RESPONSE_DATA);
        }

        $total = (int) ceil($data['QueryResponse']['totalCount'] / self::PAGE_LIMIT);
        unset($data);

        return $total;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $processDto
     * @param int           $total
     * @param CurlSender    $sender
     * @param callable      $callbackItem
     * @param RequestDto    $dto
     *
     * @return array
     */
    private function doPageLoop(
        SystemInstall $systemInstall,
        ProcessDto $processDto,
        int $total,
        CurlSender $sender,
        callable $callbackItem, RequestDto $dto): array
    {
        $requests = [];
        for ($i = 0; $i < $total; $i++) {
            $url = new Uri($dto->getUri(TRUE) . 'query?query=' . urlencode($this->getDataQuery($systemInstall,
                    $processDto, $i * self::PAGE_LIMIT + 1, self::PAGE_LIMIT)));

            $requests[] = $this
                ->fetchData($sender, RequestDto::from($dto, $url))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i + 1);
                    })
                ->then($callbackItem);
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
        if (is_array($data) && array_key_exists('QueryResponse', $data) && array_key_exists('Customer',
                $data['QueryResponse'])) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['QueryResponse']['Customer']));
            unset($data);

            return $successMessage;
        }
        throw new SystemException(
            'Quickbooks Error: Key [QueryResponse -> Customer] not found in response.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}