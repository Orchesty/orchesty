<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
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
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class AirtableSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
class AirtableSyncContactConnector implements BatchInterface, ConnectorInterface
{

    protected const PAGE_LIMIT = 50;

    /**
     * @var AirtableSystem
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
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

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
        $this->system                  = $system;
        $this->lastSyncManager         = $lastSyncManager;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->counterService          = $counterService;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'airtable-sync-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Airtable has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Airtable has not implemented "processAction" function.');
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
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';

        $promise = $this->getPage($sender, $requestDto, $callbackItem, 1, NULL, $processId);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param CurlSender    $sender
     * @param RequestDto    $requestDto
     * @param callable      $callbackItem
     * @param int           $page
     * @param DateTime|null $from
     * @param null|string   $processId
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        int $page,
        ?DateTime $from = NULL,
        ?string $processId = NULL
    ): PromiseInterface
    {
        $uri = $this->getUri($requestDto, $from);

        return $this->fetchData($sender, RequestDto::from($requestDto, $uri))->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $callbackItem, $page, $from, $processId) {
                $data = json_decode($response->getBody()->getContents(), TRUE);
                $callbackItem($this->createSuccessMessage($data, $page));

                if ($this->hasOffset($data)) {
                    return $this->getPage($sender, $requestDto, $callbackItem, $page + 1, $from, $processId);
                } else {
                    if ($processId) {
                        $this->counterService->setTotal($processId, $page * self::PAGE_LIMIT);
                    }

                    return resolve();
                }
            }
        );
    }

    /**
     * @param RequestDto    $dto
     * @param null|string   $offset
     * @param DateTime|null $from
     *
     * @return Uri
     */
    protected function getUri(RequestDto $dto, ?string $offset = NULL, ?DateTime $from = NULL): Uri
    {
        $query = NULL;
        $uri   = $dto->getUri(TRUE);

        if (strpos($uri, '?')) {
            $tmp   = explode('?', $uri);
            $uri   = $tmp[0];
            $query = $tmp[1];
        }

        $uri .= sprintf('?pageSize=%s', self::PAGE_LIMIT);
        if ($offset) {
            $uri .= sprintf('&offset=%s', $offset);
        }
        if ($query) {
            $uri .= sprintf('&', $query);
        }

        return new Uri($uri);
    }

    /**
     * @param mixed $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage($data, int $i): SuccessMessage
    {
        if (array_key_exists('records', $data)) {

            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['records']));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Bad response data for Airtable sync request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws SystemException
     */
    protected function hasOffset(array $data): bool
    {
        if (array_key_exists('offset', $data)) {
            return TRUE;
        }

        return FALSE;
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

}