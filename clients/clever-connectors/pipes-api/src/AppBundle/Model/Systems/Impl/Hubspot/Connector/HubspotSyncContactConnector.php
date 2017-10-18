<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class HubspotSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotSyncContactConnector implements BatchInterface, ConnectorInterface
{

    private const CONTACTS_URL        = '/contacts/v1/lists/all/contacts/all?count=50';
    private const CONTACTS_URL_OFFSET = '/contacts/v1/lists/all/contacts/all?count=50&vidOffset=%s';

    /**
     * @var HubspotSystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * HubspotSyncConnector constructor.
     *
     * @param HubspotSystem     $system
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     */
    public function __construct(HubspotSystem $system, DocumentManager $dm, CurlSenderFactory $factory)
    {
        $this->system                  = $system;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
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
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $url = new Uri(sprintf('%s%s', $requestDto->getUri(TRUE), self::CONTACTS_URL));

        // todo continue here (vidOffset)

        $promise = $this->fetchData($sender, RequestDto::from($requestDto, $url))
            ->then(
                function (ResponseInterface $response) use ($sender, $callbackItem, $requestDto) {
                    return all($this->doPageLoop($response, $sender, $callbackItem, $requestDto));
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
     * @param CurlSender        $sender
     * @param callable          $callbackItem
     * @param RequestDto        $dto
     *
     * @return array
     */
    private function doPageLoop(
        ResponseInterface $response,
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $dto
    ): array
    {
        $hasMore  = $this->hasMore($response);
        $requests = [];

        while ($hasMore === TRUE) {

            $vidOffset = '';
            $query     = sprintf(self::CONTACTS_URL_OFFSET, $vidOffset);
            $url       = new Uri(sprintf('%s%s', $dto->getUri(TRUE), $query));

            $requests[] = $this->fetchData($sender, RequestDto::from($dto, $url))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
                    })
                ->then($callbackItem);

            $hasMore = $this->hasMore($response);
        }

        return $requests;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     * @throws SystemException
     */
    private function hasMore(ResponseInterface $response): bool
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('has-more', $data)) {
            throw new SystemException(
                'Hubspot response has no "has-more" field!',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

        return (bool) $data['has-more'];
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
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (is_array($data) && array_key_exists('contacts', $data)) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['contacts']));
            unset($data);

            return $successMessage;
        }

        throw new SystemException(
            'Hubspot Error: Key "contacts" not found in response.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}