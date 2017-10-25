<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
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
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class WisepopsSyncEmailConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector
 */
class WisepopsSyncEmailConnector implements BatchInterface, ConnectorInterface
{

    /**
     * @var WisepopsSystem
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
     * ShopifySyncConnector constructor.
     *
     * @param WisepopsSystem    $system
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     */
    public function __construct(WisepopsSystem $system, DocumentManager $dm, CurlSenderFactory $factory)
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
        return 'wisepops-sync-email-connector';
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
        $baseUrl = $requestDto->getUri(TRUE) . 'api1/emails';

        $promise = $this->getPage($sender, $requestDto, $baseUrl, $callbackItem, 1);

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
        throw new SystemException('Wisepops has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Wisepops has not implemented "processAction" function.');
    }

    /**
     * -------------------------------------------- HELPERS --------------------------------------------
     */

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
     * @param RequestDto $requestDto
     * @param string     $baseUrl
     * @param callable   $callbackItem
     * @param int        $page
     *
     * @return PromiseInterface
     */
    private function getPage(CurlSender $sender, RequestDto $requestDto, string $baseUrl, callable $callbackItem,
                             int $page): PromiseInterface
    {
        $url = new Uri(sprintf('%s?page=%s', $baseUrl, $page));

        $res = $this->fetchData($sender, RequestDto::from($requestDto, $url))->then(
            function (ResponseInterface $response) use ($sender, $requestDto, $baseUrl, $callbackItem, $page) {
                $data = json_decode($response->getBody()->getContents());
                if (count($data) > 0) {
                    $callbackItem($this->createSuccessMessage($data, $page));

                    if (count($data) < 100) {
                        return resolve();
                    }

                    return $this->getPage($sender, $requestDto, $baseUrl, $callbackItem, $page + 1);
                } else {
                    return resolve();
                }
            }
        );

        return $res;
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
            $successMessage->setData(json_encode($data));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Bad response data for Wisepops sync request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

}