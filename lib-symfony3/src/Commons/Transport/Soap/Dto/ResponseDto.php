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
     * @param             $soapCallResponse
     * @param null|string $lastResponseHeaders
     * @param array|null  $outputHeaders
     */
    public function __construct($soapCallResponse, ?string $lastResponseHeaders, ?array $outputHeaders)
    {
        $this->soapCallResponse    = $soapCallResponse;
        $this->lastResponseHeaders = $lastResponseHeaders;
        $this->outputHeaders       = $outputHeaders;
    }

}