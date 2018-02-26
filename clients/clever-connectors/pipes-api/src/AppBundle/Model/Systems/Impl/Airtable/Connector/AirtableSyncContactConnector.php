<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class AirtableSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
class AirtableSyncContactConnector extends AirtableContactConnectorAbstract
{

    /**
     * @var ProgressCounterService
     */
    private $counterService;

    /**
     * ShipstationSyncCustomerConnector constructor.
     *
     * @param AirtableSystem         $system
     * @param LastSyncManager        $lastSyncManager
     * @param CurlSenderFactory      $factory
     * @param DocumentManager        $dm
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        AirtableSystem $system,
        LastSyncManager $lastSyncManager,
        CurlSenderFactory $factory,
        DocumentManager $dm,
        ProgressCounterService $counterService
    )
    {
        parent::__construct($system, $lastSyncManager, $factory, $dm);

        $this->counterService = $counterService;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'airtable-sync-contact-connector';
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
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';

        $table = CMHeaders::get(AirtableSystem::TABLE_URL, $dto->getHeaders());
        $view  = CMHeaders::get(AirtableSystem::VIEW, $dto->getHeaders());

        $promise = $this->getPage($sender, $requestDto, $table, $callbackItem, 1, NULL, $processId, $view,
            $systemInstall);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param CurlSender    $sender
     * @param RequestDto    $requestDto
     * @param string        $table
     * @param callable      $callbackItem
     * @param int           $page
     * @param null|string   $offset
     * @param null|string   $processId
     * @param null|string   $view
     * @param SystemInstall $systemInstall
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        string $table,
        callable $callbackItem,
        int $page,
        ?string $offset = NULL,
        ?string $processId = NULL,
        ?string $view = NULL,
        SystemInstall $systemInstall
    ): PromiseInterface
    {
        $uri = $this->getUri($table, $offset, NULL, $view);

        return $this->fetchData($sender, RequestDto::from($requestDto, $uri))
            ->then(
                function (ResponseInterface $response) use (
                    $sender, $requestDto, $table, $callbackItem, $page, $processId, $view, $systemInstall
                ) {
                    $data = json_decode($response->getBody()->getContents(), TRUE);
                    $callbackItem($this->createSuccessMessage($data, $page));

                    if ($this->hasOffset($data)) {
                        return $this->getPage(
                            $sender,
                            $requestDto,
                            $table,
                            $callbackItem,
                            $page + 1,
                            $this->getOffset($data),
                            $processId,
                            $view,
                            $systemInstall
                        );
                    } else {
                        if ($processId) {
                            $this->counterService->setTotal($processId, $page * self::PAGE_LIMIT);
                        }

                        return resolve();
                    }
                },
                function (ResponseException $e) use ($systemInstall, $callbackItem, $page) {
                    $success = $this->batchConnectorError($e, $this->system, $systemInstall, $page);

                    return $callbackItem($success);
                }
            );
    }

}