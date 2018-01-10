<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/23/17
 * Time: 11:19 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\Dto\Times;
use CleverConnectors\AppBundle\Utils\LoggerUtils;
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
 * Class QuickbooksSyncCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksSyncCustomerConnector extends QuickbooksCustomerConnectorAbstract
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
     * QuickbooksSyncCustomerConnector constructor.
     *
     * @param QuickbooksSystem       $system
     * @param LastSyncManager        $lastSyncManager
     * @param CurlSenderFactory      $factory
     * @param DocumentManager        $dm
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        QuickbooksSystem $system,
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
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-sync-customer-connector';
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
        $systemInstall = $this->getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId      = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
        $url            = new Uri(
            $requestDto->getUri(TRUE) . 'query?query=' . urlencode($this->getTotalQuery())
        );
        $counterService = $this->counterService;
        $promise        = $this->fetchData($sender, RequestDto::from($requestDto, $url))->then(
            function (ResponseInterface $response): int {
                return $this->getTotalPages($response);
            },
            function (ResponseException $exception) use ($systemInstall): void {
                if ($exception->getCode() == 401) {
                    $this->logger->info(
                        NotificationTypeEnum::ACCESS_EXPIRATION,
                        LoggerUtils::getMessage($this->system, $systemInstall)
                    );
                }
                if ($exception->getCode() == 500) {
                    $this->logger->info(
                        NotificationTypeEnum::SERVICE_UNAVAILABLE,
                        LoggerUtils::getMessage($this->system, $systemInstall)
                    );
                }
                throw $exception;
            }
        )->then(
            function (int $total) use ($sender, $callbackItem, $requestDto, $processId, $counterService) {
                $counterService->setTotal($processId, $total * self::PAGE_LIMIT);

                return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto));
            }
        );

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param Times|null $times
     *
     * @return string
     */
    protected function getTotalQuery(?Times $times = NULL): string
    {
        return 'SELECT COUNT(*) FROM customer WHERE Active = true';
    }

    /**
     * @param int        $start
     * @param int        $count
     * @param Times|null $times
     *
     * @return string
     */
    protected function getDataQuery(int $start, int $count, ?Times $times = NULL): string
    {
        return sprintf('SELECT * FROM customer WHERE Active = true STARTPOSITION %d MAXRESULTS %d', $start, $count);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    protected function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        return $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
    }

    /**
     * @param Times $times
     *
     * @return string
     */
    protected function getTimeQuery(Times $times): string
    {
        return '';
    }

}