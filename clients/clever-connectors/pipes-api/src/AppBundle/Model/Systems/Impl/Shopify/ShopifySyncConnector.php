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
use Clue\React\Buzz\Browser;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
class ShopifySyncConnector implements BatchInterface
{

    private const COUNT_URL     = 'admin/customers/count.json';
    private const CUSTOMERS_URL = 'admin/customers.json?limit=50&page=';

    /**
     * @var ShopifySystem
     */
    private $system;

    /**
     * ShopifySyncConnector constructor.
     *
     * @param ShopifySystem $system
     */
    public function __construct(ShopifySystem $system)
    {
        $this->system = $system;
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
        $countReq      = $this->createCountRequest($baseUrl);

        $promise = $this->fetchData($browser, $countReq)
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($browser, $baseUrl, $callbackItem) {
                    return all($this->doPageLoop($total, $browser, $baseUrl, $callbackItem));
                }
            );

        return $promise;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    private function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        return SystemInstall::from(json_decode($dto->getData(), TRUE));
    }

    /**
     * @param string $baseUrl
     *
     * @return RequestInterface
     */
    private function createCountRequest(string $baseUrl): RequestInterface
    {
        return new Request('GET', sprintf('%s%s', $baseUrl, self::COUNT_URL));
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
            throw new SystemException('Shopify response has no "count" field!');
        }

        return (int) ceil($data['count'] / 50);
    }

    /**
     * @param int      $total
     * @param Browser  $browser
     * @param string   $baseUrl
     * @param callable $callbackItem
     *
     * @return array
     */
    private function doPageLoop(int $total, Browser $browser, string $baseUrl, callable $callbackItem): array
    {
        $requests = [];
        for ($i = 1; $i <= $total; $i++) {
            $requests[] = $this
                ->fetchData($browser, $this->createCustomerRequest($baseUrl, $i))
                ->then(function (ResponseInterface $response) use ($i): SuccessMessage {
                    $data = json_decode($response->getBody()->getContents(), TRUE);
                    if (is_array($data) && array_key_exists('customers', $data)) {
                        $successMessage = new SuccessMessage($i);
                        $successMessage->setData(json_encode($data['customers']));

                        return $successMessage;
                    }
                    throw new SystemException('Shopify Error: Key customers not found in response');
                })->then($callbackItem);
        }

        return $requests;
    }

    /**
     * @param string $baseUrl
     * @param int    $page
     *
     * @return RequestInterface
     */
    private function createCustomerRequest(string $baseUrl, int $page): RequestInterface
    {
        return new Request('GET', sprintf('%s%s%s', $baseUrl, self::CUSTOMERS_URL, $page));
    }

}