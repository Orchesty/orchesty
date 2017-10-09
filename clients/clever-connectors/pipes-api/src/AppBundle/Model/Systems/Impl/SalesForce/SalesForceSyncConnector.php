<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 12:27
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use Clue\React\Buzz\Browser;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class SalesForceSyncConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
class SalesForceSyncConnector extends SalesForceConnectorAbstract
{

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
        $headers       = $requestDto->getHeaders();
        $baseUrl       = (string) $requestDto->getUri();
        $countRequest  = $this->createCountRequest($baseUrl, $headers);

        $promise = $this->fetchData($browser, $countRequest)
            ->then(
                function (ResponseInterface $response): int {
                    return $this->getTotalPages($response);
                }
            )->then(
                function (int $total) use ($browser, $baseUrl, $callbackItem, $headers) {
                    return all($this->doPageLoop($total, $browser, $baseUrl, $callbackItem, $headers));
                }
            );

        return $promise;
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
    protected function doPageLoop(
        int $total,
        Browser $browser,
        string $baseUrl,
        callable $callbackItem,
        array $headers
    ): array
    {
        $requests = [];
        for ($i = 0; $i < $total; $i++) {
            $requests[] = $this
                ->fetchData($browser, $this->createPageContactRequest($baseUrl, $i, $headers))
                ->then(function (ResponseInterface $response) use ($i): SuccessMessage {

                    return $this->createSuccessMessage($response, $i);
                })->then($callbackItem);
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
    private function createPageContactRequest(string $baseUrl, int $page, array $headers): RequestInterface
    {
        $query = sprintf(
            'select+email,+firstname,+lastname+from+contact+limit+%s+offset+%s',
            self::PAGE_LIMIT,
            self::PAGE_LIMIT * $page
        );

        return new Request('GET', sprintf(self::QUERY_URL, $baseUrl, $query), $headers);
    }

}