<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl\Dto;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManagerInterface;

/**
 * Class RequestDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl\Dto
 */
class RequestDto extends RequestDtoAbstract
{

    /**
     * @var Uri
     */
    private $soapServiceUri;

    /**
     * RequestDto constructor.
     *
     * @param string $function
     * @param array  $arguments
     * @param string $namespace
     * @param Uri    $soapServiceUri
     */
    public function __construct(string $function, array $arguments = [], string $namespace, Uri $soapServiceUri)
    {
        parent::__construct($function, $arguments, $namespace);

        $this->soapServiceUri = $soapServiceUri;
    }

    /**
     * @return Uri
     */
    public function getSoapServiceUri(): Uri
    {
        return $this->soapServiceUri;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SoapManagerInterface::MODE_NON_WSDL;
    }

}