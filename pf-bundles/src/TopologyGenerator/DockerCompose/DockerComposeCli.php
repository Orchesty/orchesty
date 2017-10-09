<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 6.10.17
 * Time: 23:42
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

/**
 * Class DockerComposeCli
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose
 */
class DockerComposeCli
{

    /**
     * @var string
     */
    private const DOCKER_COMPOSE_UP = 'sudo docker-compose -f {config} up -d';

    /**
     * @var string
     */
    private const DOCKER_CONFIG_FILE = 'docker-compose.yml';

    /**
     * @var string
     */
    protected $configDir;

    /**
     * DockerComposeCli constructor.
     *
     * @param string $configDir
     */
    public function __construct(string $configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * @return bool
     */
    public function up(): bool
    {
        $config = sprintf('%s/%s', $this->configDir, self::DOCKER_CONFIG_FILE);

        $command = str_replace('{config}', $config, self::DOCKER_COMPOSE_UP);
        //TODO: find better cli tool
        exec($command);

        return TRUE;
    }

}
