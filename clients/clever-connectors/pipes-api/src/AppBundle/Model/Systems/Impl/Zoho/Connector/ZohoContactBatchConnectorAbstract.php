<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Traits\ZohoLoggerTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ZohoContactBatchConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
abstract class ZohoContactBatchConnectorAbstract implements ConnectorInterface, BatchInterface, LoggerAwareInterface
{

    use ZohoLoggerTrait;

    protected const ITEMS_PER_PAGE = 50;

    /**
     * @var ZohoSystem
     */
    protected $system;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * @var ProgressCounterService
     */
    private $counterService;

    /**
     * ZohoContactConnectorAbstract constructor.
     *
     * @param ZohoSystem             $system
     * @param CurlSenderFactory      $factory
     * @param ProgressCounterService $counterService
     */
    public function __construct(
        ZohoSystem $system,
        CurlSenderFactory $factory,
        ProgressCounterService $counterService
    )
    {
        $this->system         = $system;
        $this->factory        = $factory;
        $this->counterService = $counterService;
        $this->logger         = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('ZOHO has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('ZOHO has not implemented "processAction" function.');
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
     * @param RequestDto    $requestDto
     * @param callable      $callbackItem
     * @param int           $page
     * @param SystemInstall $systemInstall
     * @param DateTime|null $from
     * @param null|string   $processId
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        int $page,
        SystemInstall $systemInstall,
        ?DateTime $from = NULL,
        ?string $processId = NULL
    ): PromiseInterface
    {
        $uri = $this->getUri($requestDto, $page, $from);

        return $this->fetchData($sender, RequestDto::from($requestDto, $uri))->then(
            function (ResponseInterface $response) use (
                $sender,
                $requestDto,
                $callbackItem,
                $page,
                $from,
                $processId,
                $systemInstall
            ) {
                $data = json_decode($response->getBody()->getContents(), TRUE);
                if (!$this->isEmpty($data)) {
                    if (array_key_exists('error', $data['response'])) {
                        $status = $data['response']['error']['code'] ?? 400;

                        return $callbackItem($this->batchConnectorError(
                            (int) $status,
                            $this->system,
                            $systemInstall,
                            $page
                        ));
                    }

                    $callbackItem($this->createSuccessMessage($data, $page));

                    return $this->getPage($sender, $requestDto, $callbackItem, $page + 1, $systemInstall, $from,
                        $processId);
                } else {
                    if ($processId) {
                        $this->counterService->setTotal($processId, $page * self::ITEMS_PER_PAGE);
                    }

                    return resolve();
                }
            }
        );
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws SystemException
     */
    protected function isEmpty(array $data): bool
    {
        if (!is_array($data) || !array_key_exists('response', $data)) {
            throw new SystemException(
                'Missing or malformed response data for ZOHO deleted request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

        return TRUE;
    }

    /**
     * @param mixed $data
     * @param int   $i
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    abstract protected function createSuccessMessage($data, int $i): SuccessMessage;

    /**
     * @param RequestDto    $dto
     * @param int           $page
     * @param DateTime|null $from
     *
     * @return Uri
     */
    abstract protected function getUri(RequestDto $dto, int $page, ?DateTime $from = NULL): Uri;

}