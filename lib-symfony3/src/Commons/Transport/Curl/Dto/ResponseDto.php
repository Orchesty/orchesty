<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl\Dto;

/**
 * Class ResponseDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl\Dto
 */
class ResponseDto
{

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $reasonPhrase;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * ResponseDto constructor.
     *
     * @param int    $statusCode
     * @param string $reasonPhrase
     * @param string $body
     * @param array  $headers
     */
    public function __construct(
        int $statusCode,
        string $reasonPhrase,
        string $body,
        array $headers
    )
    {
        $this->statusCode   = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
        $this->body         = $body;
        $this->headers      = $headers;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}