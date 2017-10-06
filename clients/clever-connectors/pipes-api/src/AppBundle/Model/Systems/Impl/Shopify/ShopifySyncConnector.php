<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.10.17
 * Time: 17:36
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Clue\React\Buzz\Browser;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
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
            ->then(function (ResponseInterface $response): float {
                $data = json_decode($response->getBody()->getContents(), TRUE);

                return ceil($data['count'] / 50);
            }
            )->then(function (float $total) use ($browser, $baseUrl, $callbackItem) {
                $requests = [];
                for ($i = 1; $i <= $total; $i++) {
                    $requests[] = $this
                        ->fetchData($browser, $this->createCustomerRequest($baseUrl, $i))
                        ->then(function (ResponseInterface $response) use ($i): SuccessMessage {
                            $successMessage = new SuccessMessage($i);
                            $successMessage->setData($response->getBody()->getContents());

                            return $successMessage;
                        })->then($callbackItem);
                }

                return all($requests);
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
     * @param int    $page
     *
     * @return RequestInterface
     */
    private function createCustomerRequest(string $baseUrl, int $page): RequestInterface
    {
        return new Request('GET', sprintf('%s%s%s', $baseUrl, self::CUSTOMERS_URL, $page));
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
     * @return Promise
     */
    protected function fetchData(Browser $browser, RequestInterface $request): Promise
    {
        return $browser->send($request);
    }

}