<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Wsdl;

use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapClient;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapException;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManagerInterface;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Wsdl\Dto\RequestDto;

/**
 * Class SoapWsdlManager
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
final class SoapWsdlManager implements SoapManagerInterface
{

    /**
     * @param RequestDto $requestDto
     * @param array      $options
     *
     * @return SoapClient
     * @throws SoapException
     */
    protected function createClient(RequestDto $requestDto, array $options): SoapClient
    {
        try {
            return new SoapClient($requestDto->getWsdlUri(), $options);
        } catch (Exception $e) {
            // TODO log
            throw new SoapException();
        }
    }

}