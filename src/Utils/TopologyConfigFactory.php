<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfigDto;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Class TopologyConfigFactory
 *
 * @package Hanaboso\PipesFramework\Utils
 */
final class TopologyConfigFactory
{

    public const ENVIRONMENT            = 'enviroment';
    public const DOCKER_REGISTRY        = 'docker_registry';
    public const DOCKER_PF_BRIDGE_IMAGE = 'docker_pf_bridge_image';
    public const RABBITMQ_HOST          = 'rabbitmq_host';
    public const RABBITMQ_USER          = 'rabbitmq_user';
    public const RABBITMQ_PASS          = 'rabbitmq_pass';
    public const RABBITMQ_VHOST         = 'rabbitmq_vhost';
    public const MULTI_PROBE_HOST       = 'multi_probe_host';
    public const METRICS_HOST           = 'metrics_host';
    public const WORKER_DEFAULT_PORT    = 'worker_default_port';

    public const NODE_CONFIG   = 'node_config';
    public const WORKER        = 'worker';
    public const TYPE          = 'type';
    public const SETTINGS      = 'settings';
    public const PUBLISH_QUEUE = 'publish_queue';
    public const FAUCET        = 'faucet';
    public const PREFETCH      = 'prefetch';

    /**
     * @param array $nodes
     *
     * @return string
     */
    public static function create(array $nodes): string
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../../.env');

        $result = [
            self::ENVIRONMENT => self::returnEnv(),
            self::NODE_CONFIG => self::loopNodes($nodes),
        ];

        return json_encode($result);
    }

    /**
     * @param array $nodes
     *
     * @return array
     */
    private static function loopNodes(array $nodes): array
    {
        $arr = [];
        /** @var Node $node */
        foreach ($nodes as $node) {
            $arr[] = [
                $node->getId() => self::returnPrefetch($node),
            ];
        }

        return $arr;
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private static function returnPrefetch(Node $node)
    {
        if (($node->getSystemConfigs())) {
            return [
                self::FAUCET => [
                    self::SETTINGS => [
                        self::PREFETCH => json_decode($node->getSystemConfigs(), TRUE, 512, JSON_THROW_ON_ERROR),
                    ],
                ],
            ];
        }
    }

    private static function returnEnv()
    {
        return [
            self::ENVIRONMENT => [
                self::DOCKER_REGISTRY        => $_ENV[self::DOCKER_REGISTRY],
                self::DOCKER_PF_BRIDGE_IMAGE => $_ENV[self::DOCKER_PF_BRIDGE_IMAGE],
                self::RABBITMQ_HOST          => $_ENV[self::RABBITMQ_HOST],
                self::RABBITMQ_USER          => $_ENV['RABBITMQ_DEFAULT_USER'],
                self::RABBITMQ_PASS          => $_ENV['RABBITMQ_DEFAULT_PASS'],
                self::RABBITMQ_VHOST         => $_ENV['RABBITMQ_DEFAULT_VHOST'],
                self::MULTI_PROBE_HOST       => $_ENV[self::MULTI_PROBE_HOST],
                self::METRICS_HOST           => $_ENV[self::METRICS_HOST],
                self::WORKER_DEFAULT_PORT    => $_ENV[self::WORKER_DEFAULT_PORT],
            ],
        ];
    }
}