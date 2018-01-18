<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:01 PM
 */

namespace CcApi\Curl;

use CcApi\Curl\Exception\CurlException;
use CcApi\Logger\NullLogger;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tracy\ILogger;

/**
 * Class CurlService
 *
 * @package CcApi\Curl
 */
class CurlSender
{

    public const GET     = 'GET';
    public const POST    = 'POST';
    public const HEAD    = 'HEAD';
    public const PUT     = 'PUT';
    public const DELETE  = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const PATCH   = 'PATCH';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var string
     */
    private $certPath;

    /**
     * @var ILogger
     */
    private $logger;

    /**
     * CurlService constructor.
     *
     * @param ClientFactory $clientFactory
     * @param string        $certPath
     */
    public function __construct(ClientFactory $clientFactory, string $certPath = '')
    {
        $this->clientFactory = $clientFactory;
        $this->certPath      = $certPath;
        $this->logger        = new NullLogger();
    }

    /**
     * @param ILogger $logger
     */
    public function setLogger(ILogger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return ResponseInterface
     * @throws CurlException
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        if ($this->certPath !== '') {
            $options['cert'] = $this->certPath;
        }

        try {
            $this->logRequest($request);

            $response = $this->clientFactory->create()->send($request, $options);

            $this->logResponse($response);

            return $response;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response !== NULL) {
                $this->logResponse($response);
            }
            $message = $response ? $response->getBody()->getContents() : $e->getMessage();

            throw new CurlException(sprintf('Curl sender error: %s', $message), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new CurlException(sprintf('Curl sender error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->clientFactory->create()->getConfig();
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

        $this->logger->log($message);
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
        $this->logger->log($message);

        // Rewind body to the beginning of the stream.
        $response->getBody()->rewind();
    }

}