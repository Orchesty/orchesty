<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Exception;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Utils\CurlMetricUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SoapFault;

/**
 * Class SoapManager
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
final class SoapManager implements SoapManagerInterface, LoggerAwareInterface
{

    public const CONNECTION_TIMEOUT = 15;

    /**
     * @var SoapClientFactory
     */
    private $soapClientFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InfluxDbSender
     */
    private $influxSender;

    /**
     * SoapManager constructor.
     *
     * @param SoapClientFactory $soapClientFactory
     * @param InfluxDbSender    $influxSender
     */
    public function __construct(SoapClientFactory $soapClientFactory, InfluxDbSender $influxSender)
    {
        $this->soapClientFactory = $soapClientFactory;
        $this->logger            = new NullLogger();
        $this->influxSender      = $influxSender;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return SoapManager
     */
    public function setLogger(LoggerInterface $logger): SoapManager
    {
        $this->logger = $logger;

        return $this;
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

            $this->logger->debug(sprintf('Request: Type: %s, Uri: %s, Headers: %s, User: %s, Password: %s',
                $request->getType(),
                $request->getUri(),
                $this->getHeadersAsString($request->getHeader()->getParams()),
                $request->getUser(),
                $request->getPassword()
            ));

            $startTimes       = CurlMetricUtils::getCurrentMetrics();
            $soapCallResponse = $client->__soapCall(
                $request->getFunction(),
                SoapHelper::composeArguments($request),
                NULL,
                SoapHelper::composeRequestHeaders($request),
                $outputHeaders
            );
            $times            = CurlMetricUtils::getTimes($startTimes);
            $info             = $request->getHeader()->getParams();
            CurlMetricUtils::sendCurlMetrics($this->influxSender, $times, $request->getUri()->__toString(),
                $info['node_id'][0] ?? NULL);

            return $this->handleResponse(
                $soapCallResponse,
                $client->__getLastResponseHeaders(),
                $outputHeaders,
                $request
            );

        } catch (SoapException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } catch (SoapFault $e) {
            $this->logger->error(sprintf('Invalid function call: %s', $e->getMessage()));
            throw new SoapException('Invalid function call.', SoapException::INVALID_FUNCTION_CALL, $e);
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unknown exception: %s', $e->getMessage()));
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

        if ($response->getResponseHeaderDto()) {
            $this->logger->debug(sprintf('Response: Status Code: %s, Reason Phrase: %s, Headers: %s, Body: %s',
                $response->getResponseHeaderDto()->getHttpStatusCode(),
                $response->getResponseHeaderDto()->getHttpReason(),
                $response->getLastResponseHeaders(),
                $response->getSoapCallResponse()
            ));
        } else {
            $this->logger->debug(sprintf('Response: Body: %s', $response->getSoapCallResponse()));
        }

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

    /**
     * @param array $headers
     *
     * @return string
     */
    private function getHeadersAsString(array $headers): string
    {
        $string = '';
        foreach ($headers as $key => $value) {
            $string .= sprintf('%s: %s, ', $key, is_array($value) ? array_values($value)[0] : $value);
        }

        return mb_substr($string, 0, -2);
    }

}