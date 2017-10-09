<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.10.17
 * Time: 17:36
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Clue\React\Buzz\Browser;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class ShopifySyncConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifySyncConnector implements BatchInterface, ConnectorInterface
{

    private const COUNT_URL     = 'admin/customers/count.json';
    private const CUSTOMERS_URL = 'admin/customers.json?limit=50&page=';

    /**
     * @var ShopifySystem
     */
    private $system;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $repo;

    /**
     * ShopifySyncConnector constructor.
     *
     * @param ShopifySystem   $system
     * @param DocumentManager $dm
     */
    public function __construct(ShopifySystem $system, DocumentManager $dm)
    {
        $this->system = $system;
        $this->dm     = $dm;
        $this->repo   = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shopify has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Shopify has not implemented "processAction" function.');
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

        $browser       = new Browser($loop);
        $systemInstall = $this->getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $baseUrl       = (string) $requestDto->getUri();
        $headers       = $requestDto->getHeaders();
        $countReq      = $this->createCountRequest($baseUrl, $headers);

        $promise = $this->fetchData($browser, $countReq)
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($browser, $baseUrl, $callbackItem, $headers) {
                    return all($this->doPageLoop($total, $browser, $baseUrl, $callbackItem, $headers));
                }
            );

        $systemInstall
            ->setSynchronized(TRUE)
            ->setSynchronizedTime(new DateTime('now', new DateTimeZone('UTC')));

        $this->dm->flush($systemInstall);

        return $promise;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    private function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        $system = SystemInstall::from(json_decode($dto->getData(), TRUE));

        return $this->repo->getSystemInstall($system->getUser(), $system->getToken(), $system->getSystem());
    }

    /**
     * @param string $baseUrl
     * @param array  $headers
     *
     * @return RequestInterface
     */
    private function createCountRequest(string $baseUrl, array $headers): RequestInterface
    {
        return new Request('GET', sprintf('%s%s', $baseUrl, self::COUNT_URL), $headers);
    }

    /**
     * @param Browser          $browser
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    protected function fetchData(Browser $browser, RequestInterface $request): PromiseInterface
    {
        return $browser->send($request);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return int
     * @throws SystemException
     */
    protected function getTotalPages(ResponseInterface $response): int
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('count', $data)) {
            throw new SystemException('Shopify response has no "count" field!', SystemException::MISSING_RESPONSE_DATA);
        }

        $total = (int) ceil($data['count'] / 50);
        unset($data);

        return $total;
    }

    /**
     * @param int      $total
     * @param Browser  $browser
     * @param string   $baseUrl
     * @param callable $callbackItem
     * @param array    $headers
     *
     * @return array
     */
    private function doPageLoop(
        int $total,
        Browser $browser,
        string $baseUrl,
        callable $callbackItem,
        array $headers
    ): array
    {
        $requests = [];
        for ($i = 1; $i <= $total; $i++) {
            $requests[] = $this
                ->fetchData($browser, $this->createCustomerRequest($baseUrl, $i, $headers))
                ->then(
                    function (ResponseInterface $response) use ($i): SuccessMessage {
                        return $this->createSuccessMessage($response, $i);
                    })
                ->then($callbackItem);
        }

        return $requests;
    }

    /**
     * @param string $baseUrl
     * @param int    $page
     * @param array  $headers
     *
     * @return RequestInterface
     */
    private function createCustomerRequest(string $baseUrl, int $page, array $headers): RequestInterface
    {
        return new Request('GET', sprintf('%s%s%s', $baseUrl, self::CUSTOMERS_URL, $page), $headers);
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
        if (is_array($data) && array_key_exists('customers', $data)) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(json_encode($data['customers']));
            unset($data);

            return $successMessage;
        }
        throw new SystemException(
            'Shopify Error: Key customers not found in response.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify';
    }

}