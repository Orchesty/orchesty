<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Wsdl\Dto;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;

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
     * @var null|string
     */
    private $headerNamespace;

    /**
     * RequestDto constructor.
     *
     * @param string      $wsdlUri
     * @param string      $function
     * @param array       $arguments
     * @param string|NULL $headerNamespace
     */
    public function __construct(
        string $wsdlUri,
        string $function,
        array $arguments = [],
        string $headerNamespace = NULL
    )
    {
        parent::__construct($function, $arguments);

        $this->wsdlUri         = $wsdlUri;
        $this->headerNamespace = $headerNamespace;
    }

    /**
     * @return string
     */
    public function getWsdlUri(): string
    {
        return $this->wsdlUri;
    }

    /**
     * @return null|string
     */
    public function getHeaderNamespace()
    {
        return $this->headerNamespace;
    }

}