<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 10.10.17
 * Time: 16:43
 */

namespace Hanaboso\PipesFramework\Commons\Docker;

use Hanaboso\PipesFramework\Commons\Docker\Endpoint\Containers;
use Hanaboso\PipesFramework\Commons\Docker\Endpoint\EndpointAbstract;
use Symfony\Component\Intl\Exception\NotImplementedException;

/**
 * Class Docker
 *
 * @package Hanaboso\PipesFramework\Commons\Docker
 */
class Docker
{

    public const COINTAINERS = 'containers';

    /**
     * @var DockerClient
     */
    protected $client;

    /**
     * Docker constructor.
     *
     * @param DockerClient $client
     */
    public function __construct(DockerClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $endpoint
     *
     * @return EndpointAbstract
     * @throws NotImplementedException
     */
    public function getEndpoint(string $endpoint): EndpointAbstract
    {
        if ($endpoint == self::COINTAINERS) {
            return new Containers($this->client);
        }

        throw new NotImplementedException($endpoint);
    }

}
