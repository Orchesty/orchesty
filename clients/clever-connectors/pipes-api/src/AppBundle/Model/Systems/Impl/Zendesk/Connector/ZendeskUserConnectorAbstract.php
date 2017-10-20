<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\ZendeskSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
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
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ZendeskUserConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
abstract class ZendeskUserConnectorAbstract implements ConnectorInterface, BatchInterface
{

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
     * @var DocumentRepository|SystemInstallRepository
     */
    protected $systemInstallRepository;

    /**
     * ZendeskSyncUserConnector constructor.
     *
     * @param ZendeskSystem     $system
     * @param LastSyncManager   $lastSyncManager
     * @param CurlSenderFactory $factory
     * @param DocumentManager   $dm
     */
    public function __construct(
        ZendeskSystem $system,
        LastSyncManager $lastSyncManager,
        CurlSenderFactory $factory,
        DocumentManager $dm
    )
    {
        $this->system                  = $system;
        $this->lastSyncManager         = $lastSyncManager;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
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
     * @param CurlSender $sender
     * @param callable   $callbackItem
     * @param RequestDto $requestDto
     * @param int        $page
     *
     * @return PromiseInterface
     */
    protected function getPage(CurlSender $sender, callable $callbackItem, RequestDto $requestDto,
                               int $page = 1): PromiseInterface
    {
        $res = $this->fetchData($sender, $requestDto)->then(
            function (ResponseInterface $response) use ($sender, $callbackItem, $requestDto, $page) {
                $data = json_decode($response->getBody()->getContents(), TRUE);
                $callbackItem($this->createSuccessMessage($data, $page));

                if (array_key_exists('next_page', $data)
                    && !is_null($data['next_page'])
                ) {
                    return $this->getPage($sender, $callbackItem,
                        RequestDto::from($requestDto, new Uri($data['next_page'])), $page + 1);
                } else {
                    return resolve();
                }
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