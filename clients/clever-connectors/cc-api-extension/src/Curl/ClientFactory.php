<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 11:05 AM
 */

namespace CcApi\Curl;

use GuzzleHttp\Client;

/**
 * Class ClientFactory
 *
 * @package CcApi\Curl
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
     *
     * @return Client
     */
    public function create(): Client
    {
        return new Client($this->config);
    }

}