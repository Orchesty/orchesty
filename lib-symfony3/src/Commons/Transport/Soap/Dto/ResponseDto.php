<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Dto;

/**
 * Class ResponseDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\Dto
 */
class ResponseDto
{

    /**
     * @var mixed
     */
    private $soapCallResponse;

    /**
     * @var null|string
     */
    private $lastResponseHeaders;

    /**
     * @var array|null
     */
    private $outputHeaders;

    /**
     * ResponseDto constructor.
     *
     * @param mixed       $soapCallResponse
     * @param null|string $lastResponseHeaders
     * @param array|null  $outputHeaders
     */
    public function __construct($soapCallResponse, ?string $lastResponseHeaders, ?array $outputHeaders)
    {
        $this->soapCallResponse    = $soapCallResponse;
        $this->lastResponseHeaders = $lastResponseHeaders;
        $this->outputHeaders       = $outputHeaders;
    }

    /**
     * @return mixed
     */
    public function getSoapCallResponse()
    {
        return $this->soapCallResponse;
    }

    /**
     * @return null|string
     */
    public function getLastResponseHeaders(): ?string
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @return array|null
     */
    public function getOutputHeaders(): ?array
    {
        return $this->outputHeaders;
    }

}