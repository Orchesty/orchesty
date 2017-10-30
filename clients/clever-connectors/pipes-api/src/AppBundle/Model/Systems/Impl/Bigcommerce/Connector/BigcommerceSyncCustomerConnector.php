<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class BigcommerceSyncCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceSyncCustomerConnector implements BatchInterface, ConnectorInterface
{

    private const COUNT_URL     = 'customers/count';
    private const CUSTOMERS_URL = 'customers?page=%s&limit=50';

    /**
     * @var BigcommerceSystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * @var ProgressCounterService
     */
    private $progressCounterService;

    /**
     * BigcommerceSyncCustomerConnector constructor.
     *
     * @param BigcommerceSystem      $system
     * @param DocumentManager        $dm
     * @param CurlSenderFactory      $factory
     * @param ProgressCounterService $progressCounterService
     */
    public function __construct(
        BigcommerceSystem $system,
        DocumentManager $dm,
        CurlSenderFactory $factory,
        ProgressCounterService $progressCounterService
    )
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->factory                 = $factory;
        $this->progressCounterService  = $progressCounterService;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Bigcommerce has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Bigcommerce has not implemented "processAction" function.');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-sync-customer-connector';
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws CleverConnectorsException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $url                    = new Uri(sprintf('%s%s', $requestDto->getUri(TRUE), self::COUNT_URL));
        $progressCounterService = $this->progressCounterService;
        $processId              = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders());

        if (!$processId) {
            throw new CleverConnectorsException(
                'Process ID not found.',
                CleverConnectorsException::PROCESS_ID_NOT_FOUND
            );
        }

        $promise = $this->fetchData($sender, RequestDto::from($requestDto, $url))
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($sender, $callbackItem, $requestDto, $processId, $progressCounterService) {
                    $progressCounterService->setTotal($processId, $total);

                    return all($this->doPageLoop($total, $sender, $callbackItem, $requestDto));
                }
            );

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

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
        $data = Json::decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('count', $data)) {
            throw new SystemException(
                'Bigcommerce response has no "count" field!',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

        $total = (int) ceil($data['count'] / 50);
        unset($data);

        return $total;
    }

    /**
     * @param int        $total
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $dto
     *
     * @return array
     */
    private function doPageLoop(int $total, CurlSender $sender, callable $callbackItem, RequestDto $dto): array
    {
        $requests = [];

        for ($i = 1; $i <= $total; $i++) {
            $url        = new Uri(sprintf($dto->getUri(TRUE) . self::CUSTOMERS_URL, $i));
            $requests[] = $this
                ->fetchData($sender, RequestDto::from($dto, $url))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
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
        $data = Json::decode($response->getBody()->getContents(), TRUE);

        if (is_array($data)) {
            $successMessage = (new SuccessMessage($i))->setData(Json::encode($data));
            unset($data);

            return $successMessage;
        }

        throw new SystemException(
            'Incorrect response for Bigcommerce synchronisation request.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}