<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;

/**
 * Class PluginSyncSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginSyncSubscriberConnector implements ConnectorInterface, BatchInterface
{

    private const PER_PAGE = 50;
    private const SUB_URL  = '/clever_connector/subscriber?page=%s&limit=%s';

    /**
     * @var SystemInterface
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
    private $counterService;

    /**
     * PluginSyncSubscriberConnector constructor.
     *
     * @param SystemInterface        $system
     * @param DocumentManager        $dm
     * @param CurlSenderFactory      $factory
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        SystemInterface $system,
        DocumentManager $dm,
        CurlSenderFactory $factory,
        ProgressCounterService $counterService
    )
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->factory                 = $factory;
        $this->counterService          = $counterService;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-sync-subscribers';
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
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';

        $promise = $this->getFirstPage(
            $sender,
            $requestDto,
            $callbackItem,
            $systemInstall->getSettings()[SystemInstall::SYSTEM_URL],
            $processId);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Plugin sync has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Plugin sync has no support for action.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * ---------------------------------- HELPERS ----------------------------------
     */

    /**
     * @param CurlSender $sender
     * @param RequestDto $dto
     *
     * @return PromiseInterface
     */
    private function fetchData(CurlSender $sender, RequestDto $dto): PromiseInterface
    {
        return $sender->send($dto);
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $requestDto
     * @param callable   $callbackItem
     * @param string     $baseUrl
     * @param string     $processId
     *
     * @return PromiseInterface
     */
    private function getFirstPage(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        string $baseUrl,
        string $processId
    ): PromiseInterface
    {
        $uri = $this->getUri($baseUrl, 1);

        $res = $this->fetchData($sender, RequestDto::from($requestDto, $uri))
            ->then(
                function (ResponseInterface $response) use (
                    $sender, $requestDto, $callbackItem, $baseUrl, $processId
                ) {
                    $data = json_decode($response->getBody()->getContents(), TRUE);

                    if (!is_array($data)
                        || !array_key_exists('total_page', $data)
                    ) {
                        throw new CleverConnectorsException(
                            'Missing or malformed data from plugin\'s sync connector.',
                            CleverConnectorsException::MISSING_DATA
                        );
                    }

                    $total = $data['total_page'];
                    $callbackItem($this->createSuccessMessage($data, 1));
                    $this->counterService->setTotal($processId, $total * self::PER_PAGE);

                    if ($total <= 1) {
                        return resolve();
                    } else {
                        return $this->getPages($sender, $requestDto, $callbackItem, $baseUrl, $total);
                    }
                }
            );

        return $res;
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $requestDto
     * @param callable   $callbackItem
     * @param string     $baseUrl
     * @param int        $total
     *
     * @return PromiseInterface
     */
    private function getPages(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        string $baseUrl,
        int $total
    ): PromiseInterface
    {
        $requests = [];

        for ($i = 2; $i <= $total; $i++) {
            $uri = $this->getUri($baseUrl, $i);

            $requests[] = $this->fetchData($sender, RequestDto::from($requestDto, $uri))
                ->then(function (ResponseInterface $response) use ($i): SuccessMessage {
                    return $this->createSuccessMessage(json_decode($response, TRUE), $i);
                })->then($callbackItem);
        }

        return all($requestDto);
    }

    /**
     * @param array $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    private function createSuccessMessage(array $data, int $i): SuccessMessage
    {
        if (array_key_exists('data', $data)) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['data']));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Missing key [data] in sync response for Plugin.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

    }

    /**
     * @param string $baseUrl
     * @param int    $page
     *
     * @return Uri
     */
    private function getUri(string $baseUrl, int $page): Uri
    {
        return new Uri(sprintf(
            '%s/%s',
            rtrim($baseUrl, '/'),
            ltrim(sprintf(self::SUB_URL, $page, self::PER_PAGE), '/')
        ));
    }

}