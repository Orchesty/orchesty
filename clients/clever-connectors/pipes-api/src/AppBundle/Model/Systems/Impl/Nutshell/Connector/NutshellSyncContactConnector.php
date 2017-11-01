<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\NutshellSystem;
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
use function React\Promise\resolve;

/**
 * Class NutshellSyncContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
class NutshellSyncContactConnector implements BatchInterface, ConnectorInterface
{

    private const PER_PAGE = 50;

    /**
     * @var NutshellSystem
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
     * NutshellSyncContactConnector constructor.
     *
     * @param NutshellSystem         $system
     * @param DocumentManager        $dm
     * @param CurlSenderFactory      $factory
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        NutshellSystem $system,
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
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Nutshell has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Nutshell has not implemented "processAction" function.');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'nutshell-sync-contact-connector';
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
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';

        /** @var Uri $url */
        $url     = $requestDto->getUri();
        $promise = $this->getPage($sender, $requestDto, $url, $callbackItem, 1, $processId);

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
     * @param CurlSender $sender
     * @param RequestDto $requestDto
     * @param Uri        $baseUrl
     * @param callable   $callbackItem
     * @param int        $page
     * @param string     $processId
     *
     * @return PromiseInterface
     */
    private function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        Uri $baseUrl,
        callable $callbackItem,
        int $page,
        string $processId
    ): PromiseInterface
    {
        $contactsDto = RequestDto::from($requestDto, $baseUrl, 'POST')->setBody(sprintf(
            '{"jsonrpc":"2.0","method":"findContacts","params":{"limit":%s,"page":%s},"id":"id"}',
            self::PER_PAGE,
            $page
        ));

        $promise = $this->fetchData($sender, $contactsDto)->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $baseUrl, $callbackItem, $page, $processId
            ) {
                $data = Json::decode($response->getBody()->getContents(), TRUE);
                if (isset($data['result']) && count($data['result']) > 0) {
                    return all($this->doPageLoop(
                        $sender,
                        $requestDto,
                        $data['result'],
                        $callbackItem,
                        $baseUrl,
                        $page
                    ))->then(function (array $promises) use (
                        $data, $sender, $requestDto, $baseUrl, $callbackItem, $page, $processId
                    ) {
                        if (count($data['result']) < self::PER_PAGE) {
                            $this->counterService->setTotal($processId, $page * self::PER_PAGE);

                            return resolve();
                        } else {
                            return $this->getPage($sender, $requestDto, $baseUrl, $callbackItem, ++$page, $processId);
                        }
                    });
                } else {
                    return resolve();
                }
            }
        );

        return $promise;
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $requestDto
     * @param array      $contacts
     * @param callable   $callbackItem
     * @param Uri        $baseUrl
     * @param int        $page
     *
     * @return array
     */
    private function doPageLoop(
        CurlSender $sender,
        RequestDto $requestDto,
        array $contacts,
        callable $callbackItem,
        Uri $baseUrl,
        int $page): array
    {
        $requests = [];
        for ($i = 0; $i < count($contacts); $i++) {
            $innerDto = RequestDto::from($requestDto, $baseUrl, 'POST')->setBody(sprintf(
                '{"jsonrpc":"2.0","method":"getContact","params":{"contactId":%s},"id":"email"}',
                $contacts[$i]['id']
            ));

            $requests[] = $this->fetchData($sender, $innerDto)
                ->then(
                    function (ResponseInterface $response) use ($page, $i): SuccessMessage {
                        $data = Json::decode($response->getBody()->getContents(), TRUE);

                        return $this->createSuccessMessage($data, ($page - 1) * self::PER_PAGE + $i + 1);
                    })
                ->then($callbackItem);
        }

        return $requests;
    }

    /**
     * @param mixed $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    private function createSuccessMessage($data, int $i): SuccessMessage
    {
        if (is_array($data)) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(Json::encode($data));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Incorrect response for Nutshell synchronisation request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

}