<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use InvalidArgumentException;
use SoapHeader;
use SoapParam;
use SoapVar;
use Symfony\Component\HttpFoundation\HeaderBag;
use function GuzzleHttp\headers_from_lines;

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
    public static function composeRequestHeaders(RequestDtoAbstract $request): ?array
    {
        $requestHeader = $request->getHeader();

        if (empty($requestHeader->getParams())) {
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
     * @return null|array
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
     * @param null|string $headers
     *
     * @return array
     */
    public static function parseResponseHeaders(?string $headers = NULL): array
    {
        $result = [
            'version'    => NULL,
            'statusCode' => NULL,
            'reason'     => NULL,
            'headers'    => NULL,
        ];

        if ($headers === NULL) {
            return $result;
        }

        $headers = explode("\n", $headers);
        $parts   = explode(' ', array_shift($headers), 3);

        if (count($parts) > 2) {
            $result['version']    = explode('/', $parts[0])[1];
            $result['statusCode'] = $parts[1];
            $result['reason']     = $parts[2] ?? NULL;
        }

        $result['headers'] = new HeaderBag(headers_from_lines($headers));

        return $result;
    }

    /**
     * @param RequestDtoAbstract $request
     *
     * @return array|null
     */
    private static function composeArgumentsForNonWsdl(RequestDtoAbstract $request): ?array
    {
        if (empty($request->getArguments())) {
            return NULL;
        }

        $soapParams = [];
        foreach ($request->getArguments() as $key => $value) {
            $soapParams[] = new SoapParam(self::composeDataForSoapParam($key, $value), $key);
        }

        return $soapParams;
    }

    /**
     * TODO may need to edit when implementing
     *
     * @param string $nodeName
     * @param mixed  $data
     *
     * @return SoapVar
     * @throws InvalidArgumentException
     */
    private static function composeDataForSoapParam(string $nodeName, $data): SoapVar
    {
        if (strpos($nodeName, ':') === FALSE) {
            $nodeName = 'ns1:' . $nodeName;
        }

        if (is_scalar($data)) {
            return new SoapVar($data, XSD_STRING, '', '', $nodeName);
        } elseif (is_array($data)) {
            $params = [];
            foreach ($data as $subName => $subArg) {
                $params[] = self::composeDataForSoapParam($subName, $subArg);
            }

            return new SoapVar($params, SOAP_ENC_OBJECT, $nodeName, NULL, $nodeName);
        }

        throw new InvalidArgumentException(sprintf('Type %s is not supported.', gettype($data)));
    }

}