<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 28.3.18
 * Time: 11:18
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Strings;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class SalesforceAppSyncSubscribersConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
class SalesforceAppSyncSubscribersConnector implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    protected const QUERY_URL      = '%s/services/data/v40.0/query?q=%s';
    protected const SYNC_STATE_URL = '%s/services/data/v40.0/pipes/sync';
    protected const PAGE_LIMIT     = 50;
    protected const NODE_NAME      = '';

    /**
     * @var SalesforceAppSystem
     */
    private $system;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * SalesforceContactConnectorAbstract constructor.
     *
     * @param SalesforceAppSystem $system
     * @param CurlSenderFactory   $factory
     * @param DocumentManager     $dm
     */
    public function __construct(SalesforceAppSystem $system, CurlSenderFactory $factory, DocumentManager $dm)
    {
        $this->system                  = $system;
        $this->factory                 = $factory;
        $this->logger                  = new NullLogger();
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'salesforceapp-sync-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('SalesforceApp has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('SalesforceApp has not implemented "processAction" function.');
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     * @throws CleverConnectorsException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $data     = json_decode($dto->getData(), TRUE);
        $listId   = $data[SalesforceAppSystem::DL_ID] ?? NULL;
        $filterId = $data[SalesforceAppSystem::FILTER_ID] ?? NULL;
        if ($listId === NULL || $filterId === NULL) {
            throw new CleverConnectorsException(
                'Parameter "distributionId" or "filterId" is missing.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $where         = sprintf('+where+CMHB__Distribution_List__r.CMHB__CM_ID__c=\'%s\'', $listId);
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        return $this->fetchData($sender, $this->createCountRequest($requestDto, $where))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                },
                function (ResponseException $e) use ($systemInstall, $callbackItem) {
                    $success = $this->batchConnectorError($e, $this->system, $systemInstall, 1);

                    return $callbackItem($success);
                }
            )->then(
                function (int $total) use ($sender, $callbackItem, $requestDto, $systemInstall, $where) {
                    return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto, $systemInstall, $where));
                }
            )->then(
                function ($all) use ($sender, $requestDto, $filterId) {
                    $this->fetchData($sender, $this->createSuccessStateRequest($requestDto, (string) $filterId));

                    return $all;
                }
            );
    }

    /**
     * @param RequestDto $dto
     * @param string     $where
     *
     * @return RequestDto
     */
    private function createCountRequest(RequestDto $dto, string $where): RequestDto
    {
        $query = sprintf('select+count()+from+CMHB__Subscriber__c%s', $where);
        $uri   = new Uri(sprintf(static::QUERY_URL, rtrim($dto->getUri(TRUE), '/'), $query));

        return RequestDto::from($dto, $uri);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return int
     * @throws SystemException
     */
    private function getTotalPages(ResponseInterface $response): int
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('totalSize', $data)) {
            throw new SystemException(
                'SalesforceApp response has no "totalSize" field!',
                SystemException::MISSING_DATA
            );
        }

        $total = (int) ceil($data['totalSize'] / self::PAGE_LIMIT);
        unset($data);

        return $total;
    }

    /**
     * @param int           $total
     * @param CurlSender    $sender
     * @param callable      $callbackItem
     * @param RequestDto    $dto
     * @param SystemInstall $systemInstall
     * @param string        $where
     *
     * @return array
     */
    private function doPageLoop(
        int $total,
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $dto,
        SystemInstall $systemInstall,
        string $where
    ): array
    {
        $requests = [];
        for ($i = 0; $i < $total; $i++) {
            $requests[] = $this
                ->fetchData($sender, $this->createPageContactRequest($i, $dto, $where))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
                    },
                    function (ResponseException $e) use ($i, $systemInstall): SuccessMessage {
                        return $this->batchConnectorError($e, $this->system, $systemInstall, $i + 1);
                    }
                )->then($callbackItem, $callbackItem);
        }

        return $requests;
    }

    /**
     * @param int        $page
     * @param RequestDto $dto
     * @param string     $where
     *
     * @return RequestDto
     */
    private function createPageContactRequest(int $page, RequestDto $dto, string $where): RequestDto
    {
        $q = 'select+CMHB__Email__c,+CMHB__Firstname__c,+CMHB__Lastname__c,' .
            '+CMHB__Distribution_List__r.CMHB__CM_ID__c,+CreatedDate,+LastModifiedDate,+CMHB__Deleted__c' .
            '+from+CMHB__Subscriber__c%s' .
            '+limit+%s+offset+%s';

        $query = sprintf($q, $where, self::PAGE_LIMIT, self::PAGE_LIMIT * $page);
        $uri   = new Uri(sprintf(self::QUERY_URL, rtrim($dto->getUri(TRUE), '/'), $query));

        return RequestDto::from($dto, $uri);
    }

    /**
     * @param ResponseInterface $response
     * @param int               $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    private function createSuccessMessage(ResponseInterface $response, int $page): SuccessMessage
    {
        $res = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($res) && array_key_exists('records', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['records']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [records] key in response data from SalesforceApp.',
            SystemException::MISSING_DATA
        );
    }

    /**
     * @param RequestDto $dto
     * @param string     $filterId
     *
     * @return RequestDto
     */
    private function createSuccessStateRequest(RequestDto $dto, string $filterId): RequestDto
    {
        $uri     = new Uri(sprintf(self::SYNC_STATE_URL, rtrim($dto->getUri(TRUE), '/')));
        $request = RequestDto::from($dto, $uri, CurlManager::METHOD_POST);
        $request->setBody(json_encode([SalesforceAppSystem::FILTER_ID => $filterId]));

        return $request;
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
     * @param CurlException|ResponseException $e
     *
     * @return bool
     */
    protected function limitReached($e): bool
    {
        return Strings::contains($e->getResponse()->getBody()->getContents(), 'REQUEST_LIMIT_EXCEEDED');
    }

}