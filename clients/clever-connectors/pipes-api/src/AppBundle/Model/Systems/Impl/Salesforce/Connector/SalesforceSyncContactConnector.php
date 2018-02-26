<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 12:27
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class SalesforceSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce
 */
class SalesforceSyncContactConnector extends SalesforceContactConnectorAbstract
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var ProgressCounterService
     */
    private $counterService;

    /**
     * SalesforceSyncConnector constructor.
     *
     * @param SalesforceSystem       $system
     * @param LastSyncManager        $lastSyncManager
     * @param CurlSenderFactory      $factory
     * @param DocumentManager        $dm
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        SalesforceSystem $system,
        LastSyncManager $lastSyncManager,
        CurlSenderFactory $factory,
        DocumentManager $dm,
        ProgressCounterService $counterService
    )
    {
        parent::__construct($system, $lastSyncManager, $factory);
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->counterService          = $counterService;
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';

        $promise = $this->fetchData($sender, $this->createCountRequest($requestDto))
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
                    $this->counterService->setTotal($processId, $total + self::PAGE_LIMIT);

                    return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto, '', $systemInstall));
                }
            );

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param int        $page
     * @param string     $timeQuery
     * @param RequestDto $dto
     *
     * @return RequestDto
     */
    protected function createPageContactRequest(int $page, string $timeQuery = '', RequestDto $dto): RequestDto
    {
        $query = sprintf(
            'select+email,+firstname,+lastname+from+contact+limit+%s+offset+%s',
            self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page
        );
        $uri   = new Uri(sprintf(self::QUERY_URL, rtrim($dto->getUri(TRUE), '/'), $query));

        return RequestDto::from($dto, $uri);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'salesforce-sync-contact-connector';
    }

}