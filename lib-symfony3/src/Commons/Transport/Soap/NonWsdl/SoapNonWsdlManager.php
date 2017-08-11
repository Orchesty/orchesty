<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl;

use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapClient;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapException;
use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapManagerInterface;

/**
 * Class SoapService
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl
 */
final class SoapNonWsdlManager implements SoapManagerInterface
{

    /**
     * @param SoapClient $client
     * @param RequestDto $request
     *
     * @return ResponseDto
     * @throws SoapException
     */
    public function send(SoapClient $client, RequestDto $request): ResponseDto
    {
        try {

            $soapCallResponse = $client->__soapCall(
                $request->getFunction(),
                $request->getArguments(),
                NULL,
                $inputHeaders,
                $outputHeaders
            );

            return new ResponseDto();

        } catch (Exception $e) {
            // TODO log
            throw new SoapException();
        }
    }

    /**
     * @param array $options
     *
     * @return SoapClient
     * @throws SoapException
     */
    protected function createClient(array $options): SoapClient
    {
        try {
            return new SoapClient(NULL, $options);
        } catch (Exception $e) {
            // TODO log
            throw new SoapException();
        }
    }

}