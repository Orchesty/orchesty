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
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
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
     * @param RequestDto $dto
     *
     * @return PromiseInterface
     */
    public function send(RequestDto $dto): PromiseInterface
    {
        $request = new Request($dto->getMethod(), $dto->getUri(), $dto->getHeaders(), $dto->getBody());

        $this->logRequest($request, $dto->getDebugInfo());

        return $this
            ->sendRequest($request)
            ->then(function (ResponseInterface $response) use ($dto) {
                $this->logResponse($response, $dto->getDebugInfo());

                return resolve($response);
            }, function (Exception $e) use ($dto) {
                if ($e instanceof ResponseException) {
                    $this->logResponse($e->getResponse(), $dto->getDebugInfo());
                } else {
                    $this->logger->error(
                        sprintf('Async request error: %s', $e->getMessage()),
                        array_merge(['exception' => $e], $dto->getDebugInfo())
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
     * @param array            $debugInfo
     */
    private function logRequest(RequestInterface $request, array $debugInfo = []): void
    {
        $message = sprintf(
            'Request: Method: %s, Uri: %s, Headers: %s, Body: "%s"',
            $request->getMethod(),
            $request->getUri(),
            $this->headersToString($request),
            $request->getBody()->getContents()
        );

        $this->logger->info($message, $debugInfo);
    }

    /**
     * @param MessageInterface $message
     *
     * @return string
     */
    private function headersToString(MessageInterface $message): string
    {
        $headers = [];
        foreach ($message->getHeaders() as $key => $values) {
            $headers[] = sprintf('%s=%s', $key, implode(", ", $values));
        }

        return implode(", ", $headers);
    }

    /**
     * @param ResponseInterface $response
     * @param array             $debugInfo
     */
    private function logResponse(ResponseInterface $response, array $debugInfo = []): void
    {
        $message = sprintf('Response: Status Code: %s, Reason Phrase: %s, Headers: %s, Body: "%s"',
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $this->headersToString($response),
            $response->getBody()->getContents()
        );
        $this->logger->info($message, $debugInfo);

        $response->getBody()->rewind();
    }

}