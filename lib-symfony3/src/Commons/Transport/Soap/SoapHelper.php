<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use SoapHeader;
use SoapParam;

/**
 * Class SoapHelper
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
class SoapHelper
{

    /**
     * @param RequestDtoAbstract $request
     *
     * @return SoapHeader[]|null
     */
    public static function composeHeaders(RequestDtoAbstract $request): ?array
    {
        $requestHeader = $request->getHeader();

        if (empty($requestHeader)) {
            return NULL;
        }

        $headers = [];
        foreach ($requestHeader->getParams() as $key => $value) {
            $headers[] = new SoapHeader($requestHeader->getNamespace(), $key, $value);
        }

        return $headers;
    }

    /**
     * @param RequestDtoAbstract $request
     *
     * @return SoapParam[]|null
     */
    public static function composeArguments(RequestDtoAbstract $request): ?array
    {
        if ($request->getType() == SoapManagerInterface::MODE_WSDL) {
            return $request->getArguments();
        } else {
            return self::composeArgumentsForNonWsdl($request);
        }
    }

    /**
     * @param RequestDtoAbstract $request
     *
     * @return array|null
     */
    private static function composeArgumentsForNonWsdl(RequestDtoAbstract $request): ?array
    {
        $arguments = $request->getArguments();
        if ($arguments === NULL) {
            return $arguments;
        }

        $soapParams = [];
        foreach ($arguments as $key => $value) {
            $soapParams[] = new SoapParam(self::composeDataForSoapParam($key, $value), $key);
        }

        return $soapParams;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return array|null
     */
    private static function composeDataForSoapParam(string $key, $value): ?array
    {
        // TODO study first how to make it universal

        return [];
    }

}