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
    private const DOCKER_COMPOSE_STOP = 'sudo docker-compose -f {config} down';

    /**
     * @var string
     */
    private const DOCKER_CONFIG_FILE = 'docker-compose.yml';

    /**
     * @var string
     */
    protected $config;

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
        $this->config    = sprintf('%s/%s', $configDir, self::DOCKER_CONFIG_FILE);
        $this->configDir = $configDir;
    }

    /**
     * @return bool
     */
    public function up(): bool
    {
        if (file_exists($this->configDir)) {
            $command = str_replace('{config}', $this->config, self::DOCKER_COMPOSE_UP);
            //TODO: find better cli tool
            exec($command);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @return bool
     */
    public function stop(): bool
    {
        if (file_exists($this->configDir)) {
            $command = str_replace('{config}', $this->config, self::DOCKER_COMPOSE_STOP);
            //TODO: find better cli tool
            exec($command);
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @return bool
     */
    public function destroy(): bool
    {
        if (file_exists($this->configDir)) {
                 exec(sprintf('rm -rf %s', $this->configDir));
            return TRUE;
        }

        return FALSE;
    }

}
