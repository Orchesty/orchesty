<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 6.10.17
 * Time: 23:42
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;

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
    private const DOCKER_STACK_DEPLOY = 'sudo docker stack deploy -c {config} {topologyprefix}';

    /**
     * @var string
     */
    private const DOCKER_STACK_REMOVE = 'sudo docker stack rm {stack}';

    /**
     * @var string
     */
    private const DOCKER_CONFIG_REMOVE = 'sudo docker config rm {stack}_config';

    /**
     * @var string
     */
    private const DOCKER_CONFIG_CREATE = 'sudo docker config create {topologyprefix}_config {file}';

    /**
     * @var string
     */
    protected $config;

    /**
     * @var string
     */
    protected $configDir;

    /**
     * @var string
     */
    private $topologyprefix;

    /**
     * DockerComposeCli constructor.
     *
     * @param string $configDir
     * @param string $topologyprefix
     */
    public function __construct(string $configDir, string $topologyprefix)
    {
        $this->config         = sprintf('%s/%s', $configDir, self::DOCKER_CONFIG_FILE);
        $this->configDir      = $configDir;
        $this->topologyprefix = $topologyprefix;
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public function up(string $mode = GeneratorHandler::MODE_SWARM): bool
    {
        if (file_exists($this->configDir)) {
            $commands = [];

            switch ($mode) {
                case GeneratorHandler::MODE_SWARM;
                    $file       = sprintf('%s/%s', $this->configDir, 'topology.json');
                    $commands[] = str_replace(['{file}', '{topologyprefix}'], [$file, $this->topologyprefix],
                        self::DOCKER_CONFIG_CREATE);
                    $commands[] = str_replace(['{config}', '{topologyprefix}'], [$this->config, $this->topologyprefix],
                        self::DOCKER_STACK_DEPLOY);
                    break;
                case GeneratorHandler::MODE_COMPOSE;
                    $commands[] = str_replace('{config}', $this->config, self::DOCKER_COMPOSE_UP);
                    break;
                default:
                    return FALSE;
            }

            //TODO: find better cli tool
            foreach ($commands as $command) {
                exec($command);
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public function stop(string $mode = GeneratorHandler::MODE_SWARM): bool
    {
        if (file_exists($this->configDir)) {
            $commands = [];

            switch ($mode) {
                case GeneratorHandler::MODE_SWARM:
                    $commands[] = str_replace(['{stack}'], [$this->topologyprefix], self::DOCKER_STACK_REMOVE);
                    $commands[] = str_replace(['{stack}'], [$this->topologyprefix], self::DOCKER_CONFIG_REMOVE);

                    break;
                case GeneratorHandler::MODE_COMPOSE:
                    $commands[] = str_replace('{config}', $this->config, self::DOCKER_COMPOSE_STOP);
                    break;
                default:
                    return FALSE;
            }

            //TODO: find better cli tool
            foreach ($commands as $command) {
                exec($command);
            }

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
