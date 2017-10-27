<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
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
 * Class BasecrmUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
class BasecrmUpdatedContactConnector implements ConnectorInterface, BatchInterface
{

    /**
     * @var BasecrmSystem
     */
    protected $system;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * BasecrmContactConnectorAbstract constructor.
     *
     * @param BasecrmSystem     $system
     * @param CurlSenderFactory $factory
     */
    public function __construct(BasecrmSystem $system, CurlSenderFactory $factory)
    {
        $this->system  = $system;
        $this->factory = $factory;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'basecrm-updated-contact-connector';
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
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $queId = $systemInstall->getSettings()[BasecrmSystem::QUE_ID];
        $uri   = new Uri(sprintf('%s/v2/sync/%s/queues/main', rtrim($requestDto->getUri(TRUE), '/'), $queId));

        $promise = $this->getPage($sender, $callbackItem, RequestDto::from($requestDto, $uri));

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
        throw new ConnectorException('BaseCRM has no support for event, updatedContactConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('BaseCRM has no support for action, updatedContactConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION);
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
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $requestDto
     * @param int        $page
     *
     * @return PromiseInterface
     */
    private function getPage(CurlSender $sender, callable $callbackItem, RequestDto $requestDto,
                             int $page = 1): PromiseInterface
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

}