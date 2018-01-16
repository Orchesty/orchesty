<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl\Dto;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;

/**
 * Class RequestDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl\Dto
 */
class RequestDto
{

    /**
     * @var string
     */
    private $method;

    /**
     * @var Uri
     */
    private $uri;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $body = '';

    /**
     * @var array
     */
    private $debugInfo = [];

    /**
     * RequestDto constructor.
     *
     * @param string $method
     * @param Uri    $uri
     *
     * @throws CurlException
     */
    public function __construct(string $method, Uri $uri)
    {
        if (!in_array($method, CurlManager::getMethods())) {
            throw new CurlException(
                sprintf('Method %s is not a valid curl method', $method),
                CurlException::INVALID_METHOD
            );
        }

        $this->method = $method;
        $this->uri    = $uri;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param bool $asString
     *
     * @return Uri|string
     */
    public function getUri($asString = FALSE)
    {
        if ($asString) {
            return (string) $this->uri;
        }

        return $this->uri;
    }

    /**
     * @param Uri $uri
     *
     * @return RequestDto
     */
    public function setUri(Uri $uri): RequestDto
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return $this
     * @throws CurlException
     */
    public function setBody(string $body)
    {
        if ($this->method == CurlManager::METHOD_GET) {
            throw new CurlException('Setting body on GET method.', CurlException::BODY_ON_GET);
        }

        $this->body = $body;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return array
     */
    public function getDebugInfo(): array
    {
        return $this->debugInfo;
    }

    /**
     * @param array $debugInfo
     *
     * @return RequestDto
     */
    public function setDebugInfo(array $debugInfo): RequestDto
    {
        $this->debugInfo = $debugInfo;

        return $this;
    }

    /**
     * @param RequestDto  $dto
     * @param Uri|null    $uri
     * @param null|string $method
     *
     * @return RequestDto
     */
    public static function from(RequestDto $dto, ?Uri $uri = NULL, ?string $method = NULL): RequestDto
    {
        $self = new self($method ?? $dto->getMethod(), $uri ?? new Uri((string) $dto->getUri(TRUE)));
        $self
            ->setHeaders($dto->getHeaders())
            ->setDebugInfo($dto->getDebugInfo());

        return $self;
    }

}