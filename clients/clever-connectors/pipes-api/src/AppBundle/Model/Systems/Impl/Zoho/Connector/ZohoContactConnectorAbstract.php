<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
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
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ZohoContactsConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
abstract class ZohoContactConnectorAbstract implements ConnectorInterface, BatchInterface
{

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
     * ZohoContactConnectorAbstract constructor.
     *
     * @param ZohoSystem        $system
     * @param CurlSenderFactory $factory
     */
    public function __construct(
        ZohoSystem $system,
        CurlSenderFactory $factory
    )
    {
        $this->system  = $system;
        $this->factory = $factory;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('ZOHO has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
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
     * @param DateTime|null $from
     *
     * @return PromiseInterface
     */
    protected function getPage(
        CurlSender $sender,
        RequestDto $requestDto,
        callable $callbackItem,
        int $page,
        ?DateTime $from = NULL
    ): PromiseInterface
    {
        $uri = $this->getUri($requestDto, $page, $from);

        return $this->fetchData($sender, RequestDto::from($requestDto, $uri))->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $callbackItem, $page, $from) {
                $data = json_decode($response->getBody()->getContents(), TRUE);
                if (!$this->isEmpty($data)) {
                    $callbackItem($this->createSuccessMessage($data, $page));

                    return $this->getPage($sender, $requestDto, $callbackItem, $page + 1, $from);
                } else {
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