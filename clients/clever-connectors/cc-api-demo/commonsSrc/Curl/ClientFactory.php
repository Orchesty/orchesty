<?php declare(strict_types=1);

namespace CleverCore\Commons\Curl;

use GuzzleHttp\Client;

/**
 * Class ClientFactory
 *
 * @package CleverCore\Commons\Curl
 */
class ClientFactory
{

    /**
     * @var array
     */
    private $config = [];

    /**
     * ClientFactory constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @return Client
     */
    public function create(): Client
    {
        return new Client($this->config);
    }

}