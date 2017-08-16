<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl;

use GuzzleHttp\Client;

/**
 * Class CurlClientFactory
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl
 */
class CurlClientFactory
{

    /**
     * @param array $config
     *
     * @return Client
     */
    public function create(array $config = []): Client
    {
        return new Client($config);
    }

}