<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
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
use function React\Promise\resolve;

/**
 * Class PipedriveSyncPersonConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
class PipedriveSyncPersonConnector implements ConnectorInterface, BatchInterface
{

    private const PERSONS_URL = '/persons?start=%s&api_token=';

    /**
     * @var PipedriveSystem
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
     * ShopifySyncConnector constructor.
     *
     * @param PipedriveSystem   $system
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     */
    public function __construct(PipedriveSystem $system, DocumentManager $dm, CurlSenderFactory $factory)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->factory                 = $factory;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pipedrive-sync-person-connector';
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

        $token   = $systemInstall->getSettings()[PipedriveSystem::API_TOKEN];
        $baseUrl = rtrim($requestDto->getUri(TRUE), '/') . self::PERSONS_URL . $token;

        $promise = $this->getPersonsPage($sender, $requestDto, $callbackItem, $baseUrl);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('PipedriveSync has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('PipedriveSync has not implemented "processAction" function.');
    }

    /**
     * ----------------------------------------------- HELPERS -----------------------------------------------
     */

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
     * @param CurlSender $sender
     * @param RequestDto $requestDto
     * @param callable   $callbackItem
     * @param string     $baseUrl
     * @param int        $page
     *
     * @return PromiseInterface
     */
    private function getPersonsPage(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        string $baseUrl,
        int $page = 0
    ): PromiseInterface
    {
        $url = new Uri(sprintf($baseUrl, $page));

        $res = $this->fetchData($sender, RequestDto::from($requestDto, $url))
            ->then(
                function (ResponseInterface $response) use ($sender, $requestDto, $callbackItem, $baseUrl, $page) {
                    $data = json_decode($response->getBody()->getContents(), TRUE);
                    $callbackItem($this->createSuccessMessage($data, $page));

                    if (!array_key_exists('additional_data', $data)
                        || !array_key_exists('pagination', $data['additional_data'])
                        || !array_key_exists('more_items_in_collection', $data['additional_data']['pagination'])
                    ) {
                        throw new CleverConnectorsException(
                            'Missing additional data for pagination of Pipedrive sync requests.',
                            CleverConnectorsException::MISSING_DATA
                        );
                    }

                    if ($data['additional_data']['pagination']['more_items_in_collection']) {
                        return $this->getPersonsPage($sender, $requestDto, $callbackItem, $baseUrl, $page + 1);
                    } else {
                        return resolve();
                    }
                }
            );

        return $res;
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
            $successMessage = new SuccessMessage($i + 1);
            $successMessage->setData(json_encode($data['data']));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Missing key [data] in sync response for Pipedrive.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

    }

}