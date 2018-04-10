<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\CampaignConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Response;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Throwable;
use function React\Promise\resolve;

/**
 * Class CMGetCampaignsConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\CampaignConnector
 */
class CMGetCampaignsConnector extends CMAuthorization implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    protected const QUERY_URL  = '%s/campaigns/standard/?count=%s&offset=%s';
    protected const PAGE_LIMIT = 100;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * @var array
     */
    private $secret;

    /**
     * SalesforceContactConnectorAbstract constructor.
     *
     * @param CurlSenderFactory $factory
     * @param array             $secret
     */
    public function __construct(CurlSenderFactory $factory, array $secret)
    {
        $this->factory = $factory;
        $this->secret  = $secret;
        $this->logger  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'clevermonitors-get-campaigns-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('CMGetCampaignsConnector has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('CMGetCampaignsConnector has not implemented "processAction" function.');
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     *
     * @throws CleverConnectorsException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop, $this->secret);
        $systemInstall = CronUtils::getSystemInstall($dto);
        $requestDto    = new RequestDto(CurlManager::METHOD_GET, new Uri($this->getBaseUrl()));
        $requestDto
            ->setHeaders($this->getAuthorizationHeaders($systemInstall->getUser(), $systemInstall->getToken()))
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $promise = $this->getPage($sender, $callbackItem, $requestDto, $systemInstall);

        return $promise;
    }

    /**
     * @param CurlSender    $sender
     * @param callable      $callbackItem
     * @param RequestDto    $requestDto
     * @param SystemInstall $systemInstall
     * @param int           $page
     *
     * @return PromiseInterface
     */
    private function getPage(
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $requestDto,
        SystemInstall $systemInstall,
        int $page = 1
    ): PromiseInterface
    {
        $uri        = new Uri(sprintf(self::QUERY_URL, $this->getBaseUrl(), self::PAGE_LIMIT, $page));
        $requestDto = RequestDto::from($requestDto, $uri);

        return $this->fetchData($sender, $requestDto)->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $callbackItem, $page, $systemInstall) {
                if ($response->getStatusCode() === 200) {
                    $callbackItem($this->createSuccessMessage($response, $page));

                    return $this->getPage($sender, $callbackItem, $requestDto, $systemInstall, $page + 1);
                }
                $this->logger->info($response->getBody()->getContents());

                return resolve();
            },
            function (ResponseException $e) use ($systemInstall, $callbackItem, $page) {
                $success = $this->batchConnectorError($e, NULL, $systemInstall, $page);
                $callbackItem($success);

                return resolve();
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
        if (is_array($res) && !empty($res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Wrong data in response from CleverMonitor.',
            SystemException::MISSING_DATA
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
        try {
            return $sender->send($request);
        } catch (Throwable $t) {
            throw new ResponseException(new Response('500'), $t->getMessage(), 500, $t);
        }
    }

}