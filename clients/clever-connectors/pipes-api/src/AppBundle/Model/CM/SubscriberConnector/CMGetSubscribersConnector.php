<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class CMGetSubscribersConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMGetSubscribersConnector extends CMAuthorization implements ConnectorInterface, BatchInterface, LoggerAwareInterface
{

    private const COUNT = 50;

    /**
     * @var CurlManagerInterface
     */
    protected $factory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CMSubscriberConnectorAbstract constructor.
     *
     * @param CurlSenderFactory $factory
     */
    function __construct(CurlSenderFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return '';
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
            'CMSubscriberConnector has no support for webhooks!',
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
            'CMGetSubscribersConnector has no support for action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return ConnectorInterface
     */
    public function setLogger(LoggerInterface $logger): ConnectorInterface
    {
        $this->logger = $logger;

        return $this;
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
        $sender = $this->factory->create($loop);
        //$systemInstall = CronUtils::getSystemInstall($dto);
        //$requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        //$requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $user   = CMHeaders::get(CMHeaders::GUID, $dto->getHeaders());
        $token  = CMHeaders::get(CMHeaders::TOKEN, $dto->getHeaders());
        $system = CMHeaders::get(CMHeaders::SYSTEM_KEY, $dto->getHeaders());

        if (!isset($user) || !isset($token) || !isset($system)) {
            throw new CleverConnectorsException(
                'User or Token or System is missing in header.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        // todo url
        $req = new RequestDto(CurlManager::METHOD_GET, new Uri($this->getUrl()));

        $req->setHeaders($this->getAuthorizationHeaders($user, $token));
        $req->setBody(json_encode($this->getData($dto, $system)));

        $queId = $systemInstall->getSettings()[BasecrmSystem::QUE_ID];
        $uri   = new Uri(sprintf('%s/v2/sync/%s/queues/main', rtrim($requestDto->getUri(TRUE), '/'), $queId));

        $promise = $this->getPage($sender, $callbackItem, RequestDto::from($requestDto, $uri));

        return $promise;
    }

    /**
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $requestDto
     * @param int        $page
     *
     * @return PromiseInterface
     */
    private function getPage(
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $requestDto,
        int $page = 1
    ): PromiseInterface
    {
        return $this->fetchData($sender, $requestDto)->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $callbackItem, $page) {
                if ($response->getStatusCode() === 200) {
                    $callbackItem($this->createSuccessMessage($response, $page));

                    return $this->getPage($sender, $callbackItem, $requestDto, $page + 1);
                } else {
                    return resolve();
                }
            }
        );
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
    private function createSuccessMessage(ResponseInterface $response, int $page): SuccessMessage
    {
        $res = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($res) && array_key_exists('items', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['items']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [items] key in response data from BaseCRM.',
            SystemException::MISSING_DATA
        );
    }

    /**
     * @param int $offset
     *
     * @return string
     */
    protected function getUrl(int $offset): string
    {
        return sprintf('https://api.dev.clevermonitor.com/v1.2/subscribers/?offset=%s&count=%s', $offset, self::COUNT);
    }

}