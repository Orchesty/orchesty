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
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->uri;
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

}