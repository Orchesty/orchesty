<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class CMGetSubscribersConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
abstract class CMGetSubscribersConnectorAbstract extends CMAuthorization implements ConnectorInterface, BatchInterface, LoggerAwareInterface
{

    protected const BASE_URL = 'https://api.dev.clevermonitor.com/v1.2';
    protected const COUNT    = 50;

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    protected $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $secret;

    /**
     * CMSubscriberConnectorAbstract constructor.
     *
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     * @param array             $secret
     */
    function __construct(DocumentManager $dm, CurlSenderFactory $factory, array $secret)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->factory                 = $factory;
        $this->secret                  = $secret;
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
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $requestDto
     * @param int        $page
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $requestDto,
        int $page = 1
    ): PromiseInterface
    {
        $requestDto->setUri(new Uri($this->getUrl(($page - 1) * self::COUNT)));

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
     * @param int $offset
     *
     * @return string
     */
    abstract protected function getUrl(int $offset): string;

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
        if (is_array($res) && !empty($res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing response data from CM.',
            SystemException::MISSING_DATA
        );
    }

}