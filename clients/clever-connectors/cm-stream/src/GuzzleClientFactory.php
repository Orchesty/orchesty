<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/23/17
 * Time: 10:44 AM
 */

namespace CmStream;

use GuzzleHttp\Client;

/**
 * Class GuzzleClientFactory
 *
 * @package CmStream
 */
class GuzzleClientFactory
{

    /**
     * @var string
     */
    private $baseUri;

    /**
     * Subscriber constructor.
     *
     * @param string $baseUri
     */
    public function __construct(string $baseUri)
    {
        $this->baseUri = $baseUri;
    }

    /**
     * @return Client
     */
    public function create(): Client
    {
        $config['base_uri'] = $this->baseUri ?: '';

        return new Client($config);
    }

}