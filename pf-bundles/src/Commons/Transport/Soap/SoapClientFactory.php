<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\Wsdl\RequestDto;
use SoapFault;

/**
 * Class SoapClientFactory
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
class SoapClientFactory
{

    /**
     * @param RequestDtoAbstract $request
     * @param array              $options
     *
     * @return SoapClient
     * @throws SoapException
     */
    public function create(RequestDtoAbstract $request, array $options): SoapClient
    {
        try {
            $wsdl = NULL;
            if ($request->getType() == SoapManagerInterface::MODE_WSDL) {
                /** @var RequestDto $request */
                $wsdl = strval($request->getUri());
            }

            return new SoapClient($wsdl, $options);

        } catch (SoapFault $e) {
            // TODO log
            throw new SoapException('Invalid WSDL.', SoapException::INVALID_WSDL, $e);
        }
    }

}