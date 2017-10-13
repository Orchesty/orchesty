<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Connector;

use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Magento2System;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
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
use React\Promise\PromiseInterface;

/**
 * Class Magento2ConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Connector
 */
abstract class Magento2ConnectorAbstract implements BatchInterface, ConnectorInterface
{

    protected const QUERY_URL  = '%s/rest/V1/customers/search?%s';
    protected const PAGE_LIMIT = 50;
    protected const NODE_NAME  = '';

    /**
     * @var Magento2System
     */
    protected $system;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * Magento2ConnectorAbstract constructor.
     *
     * @param Magento2System    $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     */
    public function __construct(Magento2System $system, LastSyncManager $lastSyncManager, CurlSenderFactory $factory)
    {
        $this->system          = $system;
        $this->lastSyncManager = $lastSyncManager;
        $this->factory         = $factory;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'magento2';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Magento2 has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Magento2 has not implemented "processAction" function.');
    }

    /**
     * @param DateTime|null $from
     * @param DateTime      $to
     *
     * @return string
     */
    protected function getTimeQuery(?DateTime $from, DateTime $to): string
    {
        $timeQuery = '';

        $i = 0;
        if ($from) {
            $timeQuery = sprintf('searchCriteria[filter_groups][0][filters][%s][field]=updated_at', $i);
            $timeQuery .= sprintf('&searchCriteria[filter_groups][0][filters][%s][condition_type]=gt', $i);
            $timeQuery .= sprintf(
                '&searchCriteria[filter_groups][0][filters][%s][value]=%s',
                $i,
                $from->format('Y-m-d H:i:s')
            );
            $i++;
        }

        $timeQuery .= sprintf(
            '%ssearchCriteria[filter_groups][0][filters][%s][field]=updated_at',
            $i === 1 ? '&' : '',
            $i
        );
        $timeQuery .= sprintf('&searchCriteria[filter_groups][0][filters][%s][condition_type]=lteq', $i);
        $timeQuery .= sprintf(
            '&searchCriteria[filter_groups][0][filters][%s][value]=%s',
            $i,
            $to->format('Y-m-d H:i:s')
        );

        return $timeQuery;
    }

    /**
     * @param RequestDto $dto
     * @param string     $timeQuery
     *
     * @return RequestDto
     */
    protected function createCountRequest(RequestDto $dto, string $timeQuery = ''): RequestDto
    {
        $query = 'searchCriteria[pageSize]=1&searchCriteria[currentPage]=1' . $timeQuery;
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
        if (is_array($res) && array_key_exists('items', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['items']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [items] key in response data from Magento2.',
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

        if (!is_array($data) || !array_key_exists('total_count', $data)) {
            throw new SystemException(
                'Magento2 response has no "total_count" field!',
                SystemException::MISSING_DATA
            );
        }

        $total = (int) ceil($data['total_count'] / self::PAGE_LIMIT);
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
        for ($i = 1; $i <= $total; $i++) {
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
     * @return RequestDto
     */
    abstract protected function createPageContactRequest(int $page, string $timeQuery, RequestDto $dto): RequestDto;

}