<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\NonWsdl;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManagerInterface;

/**
 * Class RequestDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\NonWsdl
 */
class RequestDto extends RequestDtoAbstract
{

    /**
     * @return string
     */
    public function getType(): string
    {
        return SoapManagerInterface::MODE_NON_WSDL;
    }

}