<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Wsdl\Dto\RequestDto;
use SoapFault;

/**
 * Class SoapManager
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
final class SoapManager implements SoapManagerInterface
{

    public const CONNECTION_TIMEOUT = 15;

    /**
     * @param RequestDtoAbstract $request
     * @param array              $options
     *
     * @return ResponseDto
     * @throws SoapException
     */
    public function send(RequestDtoAbstract $request, array $options = []): ResponseDto
    {
        try {

            $client = $this->createClient($request, $this->composeOptions($request, $options));

            // TODO log

            $soapCallResponse = $client->__soapCall(
                $request->getFunction(),
                SoapHelper::composeArguments($request),
                NULL,
                SoapHelper::composeHeaders($request),
                $outputHeaders
            );

            return $this->handleResponse(
                $soapCallResponse,
                $client->__getLastResponseHeaders(),
                $outputHeaders,
                $request
            );

        } catch (SoapException $e) {
            // TODO log
            throw new SoapException();
        } catch (SoapFault $e) {
            // TODO log
            throw new SoapException();
        } catch (Exception $e) {
            // TODO log
            throw new SoapException();
        }
    }

    /**
     * @param RequestDtoAbstract $request
     * @param array              $options
     *
     * @return SoapClient
     * @throws SoapException
     */
    private function createClient(RequestDtoAbstract $request, array $options): SoapClient
    {
        try {
            $wsdl = NULL;
            if ($request->getType() == SoapManagerInterface::MODE_WSDL) {
                /** @var RequestDto $request */
                $wsdl = strval($request->getWsdlUri());
            }

            return new SoapClient($wsdl, $options);

        } catch (SoapFault $e) {
            // TODO log
            throw new SoapException();
        } catch (Exception $e) {
            // TODO log
            throw new SoapException();
        }
    }

    /**
     * @param mixed              $soapCallResponse
     * @param string             $lastResponseHeaders
     * @param array              $outputHeaders
     * @param RequestDtoAbstract $request
     *
     * @return ResponseDto
     */
    private function handleResponse(
        $soapCallResponse,
        ?string $lastResponseHeaders = NULL,
        ?array $outputHeaders = NULL,
        RequestDtoAbstract $request
    ): ResponseDto
    {
        $response = new ResponseDto($soapCallResponse, $lastResponseHeaders, $outputHeaders);

        // TODO log

        return $response;
    }

    /**
     * @param RequestDtoAbstract $request
     * @param array              $options
     *
     * @return array
     */
    private function composeOptions(RequestDtoAbstract $request, array $options): array
    {
        $options['connection_timeout'] = self::CONNECTION_TIMEOUT;
        $options['features']           = SOAP_WAIT_ONE_WAY_CALLS;
        $options['trace']              = TRUE;

        if ($request->getUser() || $request->getPassword()) {
            $options['login']    = $request->getUser();
            $options['password'] = $request->getPassword();
        }

        if ($request->getVersion()) {
            $options['soap_version'] = $request->getVersion();
        }

        //Disable certificate verification
        $options['stream_context'] = stream_context_create([
            'ssl' => [
                'verify_peer'       => FALSE,
                'verify_peer_name'  => FALSE,
                'allow_self_signed' => TRUE,
            ],
        ]);

        return $options;
    }

}