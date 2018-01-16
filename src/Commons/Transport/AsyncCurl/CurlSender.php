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
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Utils\TransportFormatter;
use Hanaboso\PipesFramework\Commons\Utils\CurlMetricUtils;
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
     * @var InfluxDbSender
     */
    private $influxSender;

    /**
     * CurlSender constructor.
     *
     * @param Browser        $browser
     * @param InfluxDbSender $influxSender
     */
    public function __construct(Browser $browser, InfluxDbSender $influxSender)
    {
        $this->browser      = $browser;
        $this->logger       = new NullLogger();
        $this->influxSender = $influxSender;
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
        $startTimes = CurlMetricUtils::getCurrentMetrics();

        return $this
            ->sendRequest($request)
            ->then(function (ResponseInterface $response) use ($dto, $startTimes) {
                $this->logResponse($response, $dto->getDebugInfo());
                $times = CurlMetricUtils::getTimes($startTimes);
                CurlMetricUtils::sendCurlMetrics($this->influxSender, $times, (string) $dto->getUri(TRUE));

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
        $message = TransportFormatter::requestToString(
            $request->getMethod(),
            (string) $request->getUri(),
            $request->getHeaders(),
            $request->getBody()->getContents()
        );

        $this->logger->info($message, $debugInfo);
    }

    /**
     * @param ResponseInterface $response
     * @param array             $debugInfo
     */
    private function logResponse(ResponseInterface $response, array $debugInfo = []): void
    {
        $message = TransportFormatter::responseToString(
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getHeaders(),
            $response->getBody()->getContents()
        );
        $this->logger->info($message, $debugInfo);

        $response->getBody()->rewind();
    }

}