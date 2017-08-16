<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Dto;

use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Class ResponseHeaderDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\Dto
 */
class ResponseHeaderDto
{

    /**
     * @var HeaderBag
     */
    private $httpHeaders;

    /**
     * @var null|string
     */
    private $httpVersion;

    /**
     * @var int|null
     */
    private $httpStatusCode;

    /**
     * @var null|string
     */
    private $httpReason;

    /**
     * ResponseHeaderDto constructor.
     *
     * @param HeaderBag   $httpHeaders
     * @param string|NULL $httpVersion
     * @param int         $httpStatusCode
     * @param string|NULL $httpReason
     */
    public function __construct(
        ?HeaderBag $httpHeaders,
        ?string $httpVersion,
        ?int $httpStatusCode,
        ?string $httpReason
    )
    {

        $this->httpHeaders    = $httpHeaders;
        $this->httpVersion    = $httpVersion;
        $this->httpStatusCode = $httpStatusCode;
        $this->httpReason     = $httpReason;
    }

    /**
     * @return HeaderBag
     */
    public function getHttpHeaders(): HeaderBag
    {
        return $this->httpHeaders;
    }

    /**
     * @return null|string
     */
    public function getHttpVersion(): ?string
    {
        return $this->httpVersion;
    }

    /**
     * @return int|null
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * @return null|string
     */
    public function getHttpReason(): ?string
    {
        return $this->httpReason;
    }

}