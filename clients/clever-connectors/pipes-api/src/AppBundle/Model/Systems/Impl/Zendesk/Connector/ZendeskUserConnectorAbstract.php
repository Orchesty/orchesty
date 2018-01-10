<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\ZendeskSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Clue\React\Buzz\Message\ResponseException;
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
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ZendeskUserConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
abstract class ZendeskUserConnectorAbstract implements ConnectorInterface, BatchInterface, LoggerAwareInterface
{

    use LoggerTrait;

    protected const PER_PAGE = 50;

    /**
     * @var ZendeskSystem
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
     * @var ObjectRepository|SystemInstallRepository
     */
    protected $systemInstallRepository;

    /**
     * @var ProgressCounterService
     */
    private $counterService;

    /**
     * ZendeskSyncUserConnector constructor.
     *
     * @param ZendeskSystem          $system
     * @param LastSyncManager        $lastSyncManager
     * @param CurlSenderFactory      $factory
     * @param DocumentManager        $dm
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        ZendeskSystem $system,
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
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Zendesk has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('Zendesk has not implemented "processAction" function.');
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
     * @param CurlSender    $sender
     * @param callable      $callbackItem
     * @param RequestDto    $requestDto
     * @param int           $page
     * @param string        $processId
     * @param SystemInstall $systemInstall
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $requestDto,
        int $page = 1,
        ?string $processId = NULL,
        SystemInstall $systemInstall
    ): PromiseInterface
    {
        $res = $this->fetchData($sender, $requestDto)
            ->then(
                function (ResponseInterface $response) use (
                    $sender, $callbackItem, $requestDto, $page, $processId, $systemInstall
                ) {
                    $data = json_decode($response->getBody()->getContents(), TRUE);
                    $callbackItem($this->createSuccessMessage($data, $page));

                    if (array_key_exists('next_page', $data) && !is_null($data['next_page'])) {
                        return $this->getPage(
                            $sender,
                            $callbackItem,
                            RequestDto::from($requestDto, new Uri($data['next_page'])),
                            ++$page,
                            NULL,
                            $systemInstall
                        );
                    } else {
                        if ($processId) {
                            $this->counterService->setTotal($processId, $page * self::PER_PAGE);
                        }

                        return resolve();
                    }
                },
                function (ResponseException $e) use ($systemInstall): void {
                    $this->logError($e->getResponse()->getStatusCode(), $this->system, $systemInstall);
                    throw $e;
                }
            );

        return $res;
    }

    /**
     * @param mixed $data
     * @param int   $page
     *
     * @return SuccessMessage
     */
    abstract protected function createSuccessMessage($data, int $page): SuccessMessage;

}