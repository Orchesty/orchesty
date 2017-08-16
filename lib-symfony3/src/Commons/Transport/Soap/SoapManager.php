<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\ResponseDto;
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
     * @var SoapClientFactory
     */
    private $soapClientFactory;

    /**
     * SoapManager constructor.
     *
     * @param SoapClientFactory $soapClientFactory
     */
    public function __construct(SoapClientFactory $soapClientFactory)
    {
        $this->soapClientFactory = $soapClientFactory;
    }

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

            $client = $this->soapClientFactory->create($request, $this->composeOptions($request, $options));

            // TODO log

            $soapCallResponse = $client->__soapCall(
                $request->getFunction(),
                SoapHelper::composeArguments($request),
                NULL,
                SoapHelper::composeRequestHeaders($request),
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
            throw $e;
        } catch (SoapFault $e) {
            // TODO log
            throw new SoapException('Invalid function call.', SoapException::INVALID_FUNCTION_CALL, $e);
        } catch (Exception $e) {
            // TODO log
            throw new SoapException('Unknown exception.', SoapException::UNKNOWN_EXCEPTION, $e);
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

        // TODO log - may use request object
        count([$request]);

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