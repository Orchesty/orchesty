<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Transport\Curl;

use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Transport\Curl\CurlClientFactory;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;

/**
 * Class CMCurlManager
 *
 * @package CleverConnectors\AppBundle\Transport\Curl
 */
final class CMCurlManager extends CurlManager
{

    /**
     * @var array
     */
    private $secret;

    /**
     * CMCurlManager constructor.
     *
     * @param CurlClientFactory $curlClientFactory
     * @param InfluxDbSender    $influxSender
     * @param array             $secret
     */
    public function __construct(CurlClientFactory $curlClientFactory, InfluxDbSender $influxSender, array $secret)
    {
        parent::__construct($curlClientFactory, $influxSender);

        $this->secret = $secret;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function prepareOptions(array $options): array
    {
        $secretOptions = [
            RequestOptions::CERT    => $this->secret['cert'],
            RequestOptions::SSL_KEY => $this->secret['cert'],
            RequestOptions::VERIFY  => $this->secret['ca'],
        ];

        return array_merge($options, $secretOptions);
    }

}