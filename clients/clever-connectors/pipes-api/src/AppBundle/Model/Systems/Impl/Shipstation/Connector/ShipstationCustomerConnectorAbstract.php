<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\ShipstationSystem;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;

/**
 * Class ShipstationCustomerConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Connector
 */
abstract class ShipstationCustomerConnectorAbstract implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    protected const QUERY_URL  = '%s/customers?%s';
    protected const PAGE_LIMIT = 50;
    protected const NODE_NAME  = '';

    /**
     * @var ShipstationSystem
     */
    protected $system;

    /**
     * @var LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * ShipstationCustomerConnectorAbstract constructor.
     *
     * @param ShipstationSystem $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     */
    public function __construct(ShipstationSystem $system, LastSyncManager $lastSyncManager, CurlSenderFactory $factory)
    {
        $this->system          = $system;
        $this->lastSyncManager = $lastSyncManager;
        $this->factory         = $factory;
        $this->logger          = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Shipstation has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Shipstation has not implemented "processAction" function.');
    }

    /**
     * @param RequestDto $dto
     *
     * @return RequestDto
     */
    protected function createCountRequest(RequestDto $dto): RequestDto
    {
        $query = 'page=1&pageSize=1';
        $uri   = new Uri(sprintf(static::QUERY_URL, $dto->getUri(TRUE), $query));

        return RequestDto::from($dto, $uri);
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
     * @param int               $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage(ResponseInterface $response, int $page): SuccessMessage
    {
        $res = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($res) && array_key_exists('customers', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['customers']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [customers] key in response data from Shipstation.',
            SystemException::MISSING_DATA
        );
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

        if (!is_array($data) || !array_key_exists('total', $data)) {
            throw new SystemException(
                'Shipstation response has no "total" field!',
                SystemException::MISSING_DATA
            );
        }

        $total = (int) ceil($data['total'] / self::PAGE_LIMIT);
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
     */
    protected function doPageLoop(
        int $total,
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $dto,
        SystemInstall $systemInstall
    ): array
    {
        $requests = [];
        for ($i = 1; $i <= $total; $i++) {
            $requests[] = $this
                ->fetchData($sender, $this->createPageContactRequest($i, $dto))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
                    },
                    function (ResponseException $e) use ($systemInstall, $i): SuccessMessage {
                        return $this->batchConnectorError($e, $this->system, $systemInstall, $i + 1);
                    }
                )->then($callbackItem, $callbackItem);
        }

        return $requests;
    }

    /**
     * @param int        $page
     * @param RequestDto $dto
     *
     * @return RequestDto
     */
    abstract protected function createPageContactRequest(int $page, RequestDto $dto): RequestDto;

}