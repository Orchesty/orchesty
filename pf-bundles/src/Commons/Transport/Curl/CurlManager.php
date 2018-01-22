<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl;

use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Commons\Transport\Utils\TransportFormatter;
use Hanaboso\PipesFramework\Commons\Utils\CurlMetricUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class CurlManager
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl
 */
class CurlManager implements CurlManagerInterface, LoggerAwareInterface
{

    public const METHOD_GET     = 'GET';
    public const METHOD_POST    = 'POST';
    public const METHOD_HEAD    = 'HEAD';
    public const METHOD_PUT     = 'PUT';
    public const METHOD_DELETE  = 'DELETE';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_PATCH   = 'PATCH';

    /**
     * @var CurlClientFactory
     */
    private $curlClientFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InfluxDbSender
     */
    private $influxSender;

    /**
     * CurlManager constructor.
     *
     * @param CurlClientFactory $curlClientFactory
     * @param InfluxDbSender    $influxSender
     */
    public function __construct(CurlClientFactory $curlClientFactory, InfluxDbSender $influxSender)
    {
        $this->curlClientFactory = $curlClientFactory;
        $this->logger            = new NullLogger();
        $this->influxSender      = $influxSender;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return CurlManager
     */
    public function setLogger(LoggerInterface $logger): CurlManager
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return array
     */
    public static function getMethods(): array
    {
        return [
            self::METHOD_GET,
            self::METHOD_POST,
            self::METHOD_HEAD,
            self::METHOD_PUT,
            self::METHOD_DELETE,
            self::METHOD_OPTIONS,
            self::METHOD_PATCH,
        ];
    }

    /**
     * @param RequestDto $dto
     * @param array      $options
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function send(RequestDto $dto, array $options = []): ResponseDto
    {
        $startTimes = CurlMetricUtils::getCurrentMetrics();
        $request    = new Request($dto->getMethod(), $dto->getUri(), $dto->getHeaders(), $dto->getBody());
        try {

            $this->logger->info(TransportFormatter::requestToString(
                $dto->getMethod(),
                (string) $dto->getUri(),
                $dto->getHeaders(),
                $dto->getBody()
            ));

            $client = $this->curlClientFactory->create();

            $psrResponse = $client->send($request, $this->prepareOptions($options));
            $times       = CurlMetricUtils::getTimes($startTimes);
            CurlMetricUtils::sendCurlMetrics($this->influxSender, $times, $request->getUri()->__toString());

            $response = new ResponseDto(
                $psrResponse->getStatusCode(),
                $psrResponse->getReasonPhrase(),
                $psrResponse->getBody()->getContents(),
                $psrResponse->getHeaders()
            );

            $this->logger->info(TransportFormatter::responseToString(
                $psrResponse->getStatusCode(),
                $psrResponse->getReasonPhrase(),
                $psrResponse->getHeaders(),
                $psrResponse->getBody()->getContents()
            ));

            unset($psrResponse);
        } catch (RequestException $exception) {
            $times = CurlMetricUtils::getTimes($startTimes);
            CurlMetricUtils::sendCurlMetrics($this->influxSender, $times, $request->getUri()->__toString());
            $response = $exception->getResponse();
            $message  = $exception->getMessage();
            if ($response) {
                $message = $response->getBody()->getContents();
                $response->getBody()->rewind();
            }
            $this->logger->error(sprintf('CurlManager::send() failed: %s', $message));

            throw new CurlException(
                sprintf('CurlManager::send() failed: %s', $message),
                CurlException::REQUEST_FAILED,
                $exception->getPrevious(),
                $response
            );
        } catch (Exception $exception) {
            $times = CurlMetricUtils::getTimes($startTimes);
            CurlMetricUtils::sendCurlMetrics($this->influxSender, $times, $request->getUri()->__toString());
            $this->logger->error(sprintf('CurlManager::send() failed: %s', $exception->getMessage()));
            throw new CurlException(
                sprintf('CurlManager::send() failed: %s', $exception->getMessage()),
                CurlException::REQUEST_FAILED,
                $exception->getPrevious()
            );
        }

        return $response;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function prepareOptions(array $options): array
    {
        return $options;
    }

}