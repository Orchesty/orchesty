<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
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
        parent::__construct($system, $lastSyncManager, $factory);

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

}