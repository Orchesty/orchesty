<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Dto;

use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapHelper;

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
     * @var ResponseHeaderDto
     */
    private $responseHeaderDto;

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

        //@todo fatal error
        $parsedHeaders           = SoapHelper::parseResponseHeaders($outputHeaders);
        $this->responseHeaderDto = new ResponseHeaderDto(
            $parsedHeaders['headers'],
            $parsedHeaders['version'],
            $parsedHeaders['statusCode'],
            $parsedHeaders['reason']
        );
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
     * @return ResponseHeaderDto|null
     */
    public function getResponseHeaderDto(): ?ResponseHeaderDto
    {
        return $this->responseHeaderDto;
    }

}