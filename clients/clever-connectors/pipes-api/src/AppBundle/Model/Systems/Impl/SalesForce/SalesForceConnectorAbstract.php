<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use DateTime;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Class SalesForceConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
abstract class SalesForceConnectorAbstract implements BatchInterface, ConnectorInterface
{

    protected const QUERY_URL  = '%s/services/data/v40.0/query?q=%s';
    protected const PAGE_LIMIT = 50;
    protected const NODE_NAME  = '';

    /**
     * @var SalesForceSystem
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
     * SalesForceDeleteConnector constructor.
     *
     * @param SalesForceSystem  $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     */
    public function __construct(SalesForceSystem $system, LastSyncManager $lastSyncManager, CurlSenderFactory $factory)
    {
        $this->system          = $system;
        $this->lastSyncManager = $lastSyncManager;
        $this->factory         = $factory;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('SalesForce has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('SalesForce has not implemented "processAction" function.');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'salesforce';
    }

    /**
     * @param DateTime|null $from
     * @param DateTime      $to
     *
     * @return string
     */
    protected function getTimeQuery(?DateTime $from, DateTime $to): string
    {
        $timeQuery = '+';

        if ($from) {
            $timeQuery .= ltrim(http_build_query(['q' => 'where LastModifiedDate>' . $from->format(DateTime::ISO8601)]),
                'q=');
        }
        $timeQuery .= ($timeQuery === '+' ? '' : 'and+') .
            ltrim(http_build_query(['q' => 'where LastModifiedDate<=' . $to->format(DateTime::ISO8601)]), 'q=');

        return $timeQuery;
    }

    /**
     * @param RequestDto $dto
     * @param string     $timeQuery
     *
     * @return RequestInterface
     */
    protected function createCountRequest(RequestDto $dto, string $timeQuery = ''): RequestInterface
    {
        $query = 'select+count()+from+contact' . $timeQuery;

        return new Request('GET', sprintf(static::QUERY_URL, $dto->getUri(TRUE), $query), $dto->getHeaders());
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
     * @param int               $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage(ResponseInterface $response, int $page): SuccessMessage
    {
        $res = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($res) && array_key_exists('records', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['records']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [records] key in response data from SalesForce.',
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

        if (!is_array($data) || !array_key_exists('totalSize', $data)) {
            throw new SystemException(
                'SalesForce response has no "totalSize" field!',
                SystemException::MISSING_DATA
            );
        }

        $total = (int) ceil($data['totalSize'] / self::PAGE_LIMIT);
        unset($data);

        return $total;
    }

    /**
     * @param int        $total
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $dto
     * @param string     $timeQuery
     *
     * @return array
     */
    protected function doPageLoop(
        int $total,
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $dto,
        string $timeQuery = ''
    ): array
    {
        $requests = [];
        for ($i = 0; $i < $total; $i++) {
            $requests[] = $this
                ->fetchData($sender, $this->createPageContactRequest($i, $timeQuery, $dto))
                ->then(function (ResponseInterface $response) use ($i): SuccessMessage {

                    return $this->createSuccessMessage($response, $i);
                })->then($callbackItem);
        }

        return $requests;
    }

    /**
     * @param int        $page
     * @param string     $timeQuery
     * @param RequestDto $dto
     *
     * @return RequestInterface
     */
    abstract protected function createPageContactRequest(
        int $page,
        string $timeQuery,
        RequestDto $dto
    ): RequestInterface;

}