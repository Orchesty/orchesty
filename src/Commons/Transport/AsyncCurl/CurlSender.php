<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 13:41
 */

namespace Hanaboso\PipesFramework\Commons\Transport\AsyncCurl;

use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use Exception;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class CurlSender
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Async\Curl
 */
class CurlSender implements LoggerAwareInterface
{

    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CurlSender constructor.
     *
     * @param Browser $browser
     */
    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
        $this->logger  = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    public function send(RequestInterface $request): PromiseInterface
    {
        $this->logRequest($request);

        return $this
            ->sendRequest($request)
            ->then(function (ResponseInterface $response) {
                $this->logResponse($response);

                return resolve($response);
            }, function (Exception $e) {
                if ($e instanceof ResponseException) {
                    $this->logResponse($e->getResponse());
                } else {
                    $this->logger->error(
                        sprintf('Async request error: %s', $e->getMessage()),
                        ['exception' => $e]
                    );
                }

                return reject($e);
            });
    }

    /**
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    private function sendRequest(RequestInterface $request): PromiseInterface
    {
        return $this->browser->send($request);
    }

    /**
     * @param RequestInterface $request
     */
    private function logRequest(RequestInterface $request): void
    {
        $message = sprintf(
            'Request: Method: %s, Uri: %s, Headers: %s, Body: %s',
            $request->getMethod(),
            $request->getUri(),
            $this->headersToString($request),
            $request->getBody()->getContents()
        );

        $this->logger->info($message);
    }

    /**
     * @param MessageInterface $message
     *
     * @return string
     */
    private function headersToString(MessageInterface $message): string
    {
        $headers = '';
        foreach ($message->getHeaders() as $name => $values) {
            $headers .= $name . ": " . implode(", ", $values);
        }

        return $headers;
    }

    /**
     * @param ResponseInterface $response
     */
    private function logResponse(ResponseInterface $response): void
    {
        $message = sprintf('Response: Status Code: %s, Reason Phrase: %s, Headers: %s, Body: %s',
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $this->headersToString($response),
            $response->getBody()->getContents()
        );
        $this->logger->info($message);

        $response->getBody()->rewind();
    }

}