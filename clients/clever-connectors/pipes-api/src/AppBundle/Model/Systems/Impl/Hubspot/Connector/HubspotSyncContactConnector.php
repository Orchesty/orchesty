<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class HubspotSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotSyncContactConnector implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const PER_PAGE            = 50;
    private const CONTACTS_URL        = '/contacts/v1/lists/all/contacts/all?count=' . self::PER_PAGE;
    private const CONTACTS_URL_OFFSET = '/contacts/v1/lists/all/contacts/all?count=' . self::PER_PAGE . '&vidOffset=%s';

    /**
     * @var HubspotSystem
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
     * HubspotSyncConnector constructor.
     *
     * @param HubspotSystem          $system
     * @param DocumentManager        $dm
     * @param CurlSenderFactory      $factory
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        HubspotSystem $system,
        DocumentManager $dm,
        CurlSenderFactory $factory,
        ProgressCounterService $counterService
    )
    {
        $this->system                  = $system;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->counterService          = $counterService;
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hubspot-sync-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Hubspot has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Hubspot has not implemented "processAction" function.');
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
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
        $url       = new Uri(sprintf('%s%s', $requestDto->getUri(TRUE), self::CONTACTS_URL));
        $promise   = $this->getPage($sender, $callbackItem, $requestDto, $url, 1, $processId, $systemInstall);

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
     * @param CurlSender    $sender
     * @param callable      $callbackItem
     * @param RequestDto    $dto
     * @param Uri           $url
     * @param int           $page
     * @param string        $processId
     * @param SystemInstall $systemInstall
     *
     * @return PromiseInterface
     */
    private function getPage(
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $dto,
        Uri $url,
        int $page,
        string $processId,
        SystemInstall $systemInstall
    ): PromiseInterface
    {
        $res = $this->fetchData($sender, RequestDto::from($dto, $url))
            ->then(
                function (ResponseInterface $response)
                use ($sender, $callbackItem, $dto, $url, $page, $processId, $systemInstall) {
                    $body   = json_decode($response->getBody()->getContents(), TRUE);
                    $parsed = $this->checkParsedResponseData($body);
                    $callbackItem($this->createSuccessMessage($body, $page));

                    if ($parsed['has-more'] === TRUE) {
                        $query = sprintf(self::CONTACTS_URL_OFFSET, $parsed['vid-offset']);
                        $url   = new Uri(sprintf('%s%s', $dto->getUri(TRUE), $query));

                        return $this->getPage($sender, $callbackItem, $dto, $url, ++$page, $processId, $systemInstall);
                    } else {
                        $this->counterService->setTotal($processId, $page * self::PER_PAGE);
                    }

                    return resolve();
                },
                function (ResponseException $e) use ($systemInstall, $callbackItem, $page) {
                    $success = $this->batchConnectorError($e, $this->system, $systemInstall, $page);

                    return $callbackItem($success);
                }
            );

        return $res;
    }

    /**
     * @param array $body
     *
     * @return array
     * @throws SystemException
     */
    private function checkParsedResponseData(array $body): array
    {
        if (!is_array($body) || !array_key_exists('has-more', $body) || !array_key_exists('vid-offset', $body)) {
            throw new SystemException(
                'Hubspot response has no "has-more" or "vid-offset" field!',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

        return $body;
    }

    /**
     * @param array $body
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    private function createSuccessMessage(array $body, int $i): SuccessMessage
    {
        if (is_array($body) && array_key_exists('contacts', $body)) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($body['contacts']));
            unset($body);

            return $successMessage;
        }

        throw new SystemException(
            'Hubspot Error: Key "contacts" not found in response.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}