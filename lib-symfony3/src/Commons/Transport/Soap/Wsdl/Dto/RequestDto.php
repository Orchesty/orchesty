<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Wsdl\Dto;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManagerInterface;

/**
 * Class RequestDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\Wsdl\Dto
 */
class RequestDto extends RequestDtoAbstract
{

    /**
     * @var string
     */
    private $wsdlUri;

    /**
     * RequestDto constructor.
     *
     * @param string $function
     * @param array  $arguments
     * @param string $namespace
     * @param Uri    $wsdlUri
     */
    public function __construct(string $function, array $arguments = [], string $namespace, Uri $wsdlUri)
    {
        parent::__construct($function, $arguments, $namespace);

        $this->wsdlUri = $wsdlUri;
    }

    /**
     * @return Uri
     */
    public function getWsdlUri(): Uri
    {
        return $this->wsdlUri;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SoapManagerInterface::MODE_WSDL;
    }

}