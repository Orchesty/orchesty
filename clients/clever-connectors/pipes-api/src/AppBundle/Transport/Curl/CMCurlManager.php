<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Transport\Curl;

use GuzzleHttp\RequestOptions;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlClientFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;

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
     * CurlManager constructor.
     *
     * @param CurlClientFactory $curlClientFactory
     * @param array             $secret
     */
    public function __construct(CurlClientFactory $curlClientFactory, array $secret)
    {
        parent::__construct($curlClientFactory);

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