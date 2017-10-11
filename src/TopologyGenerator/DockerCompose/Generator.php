<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:27 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\CounterServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\NodeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\ProbeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorInterface;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use Hanaboso\PipesFramework\TopologyGenerator\HostMapper;

/**
 * Class Generator
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class Generator implements GeneratorInterface
{

    public const REGISTRY = 'dkr.hanaboso.net/pipes/pipes';

    /**
     * @var ComposeBuilder
     */
    private $composeBuilder;

    /**
     * @var HostMapper
     */
    private $hostMapper;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var string
     */
    private $network;

    /**
     * @var VolumePathDefinitionFactory
     */
    private $volumePathDefinitionFactory;

    /**
     * Generator constructor.
     *
     * @param Environment                 $environment
     * @param HostMapper                  $hostMapper
     * @param string                      $targetDir
     * @param string                      $network
     * @param VolumePathDefinitionFactory $volumePathDefinitionFactory
     */
    public function __construct(
        Environment $environment,
        HostMapper $hostMapper,
        string $targetDir,
        string $network,
        VolumePathDefinitionFactory $volumePathDefinitionFactory
    )
    {
        $this->environment                 = $environment;
        $this->hostMapper                  = $hostMapper;
        $this->targetDir                   = $targetDir;
        $this->network                     = $network;
        $this->composeBuilder              = new ComposeBuilder();
        $this->volumePathDefinitionFactory = $volumePathDefinitionFactory;
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     *
     * @return string
     */
    public function createTopologyConfig(Topology $topology, iterable $nodes): string
    {
        $config['id'] = GeneratorUtils::normalizeName($topology->getId(), $topology->getName());

        foreach ($nodes as $node) {
            $nodeFullId = GeneratorUtils::normalizeName($node->getId(), $node->getName());

            $nodeConfig          = [];
            $nodeConfig['id']    = $nodeFullId;
            $nodeConfig['label'] = [
                'id'        => $nodeFullId,
                'node_id'   => $node->getId(),
                'node_name' => $node->getName(),
            ];
            $nodeConfig['worker'] = $this->getWorkerConfig($node);
            $nodeConfig['next']   = [];
            foreach ($node->getNext() as $next) {
                $nodeConfig['next'][] = GeneratorUtils::normalizeName($next->getId(), $next->getName());
            }

            $config['nodes'][] = $nodeConfig;
        }

        return json_encode($config);
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     *
     * @return string
     */
    public function createCompose(Topology $topology, iterable $nodes): string
    {
        $volumePathDefinition = $this->volumePathDefinitionFactory->create($topology);

        $compose = new Compose();

        $compose->addNetwork($this->network);

        $builder = new ProbeServiceBuilder(
            $this->environment,
            self::REGISTRY,
            $this->network,
            $topology,
            $volumePathDefinition
        );

        $nodeWatcherService = $builder->build(new Node());
        $compose->addServices($nodeWatcherService);

        $builder = new CounterServiceBuilder(
            $this->environment,
            self::REGISTRY,
            $this->network,
            $volumePathDefinition
        );

        $counterService = $builder->build(new Node());
        $compose->addServices($counterService);

        foreach ($nodes as $node) {
            $builder = new NodeServiceBuilder(
                $this->environment,
                self::REGISTRY,
                $this->network,
                $volumePathDefinition
            );
            $compose->addServices($builder->build($node));
        }

        return $this->composeBuilder->build($compose);
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     */
    public function generate(Topology $topology, iterable $nodes): void
    {
        if (!is_dir(self::getTopologyDir($topology, $this->targetDir))) {
            mkdir(self::getTopologyDir($topology, $this->targetDir));
        }

        file_put_contents(
            self::getTopologyDir($topology, $this->targetDir) . '/topology.json',
            $this->createTopologyConfig($topology, $nodes)
        );

        file_put_contents(
            self::getTopologyDir($topology, $this->targetDir) . '/docker-compose.yml',
            $this->createCompose($topology, $nodes)
        );
    }

    /**
     * @param Topology $topology
     *
     * @param string   $targetDir
     *
     * @return string
     */
    public static function getTopologyDir(Topology $topology, string $targetDir): string
    {
        return sprintf(
            '%s/%s',
            $targetDir,
            GeneratorUtils::normalizeName($topology->getId(), $topology->getName())
        );
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getWorkerConfig(Node $node): array
    {
        switch ($node->getType()) {
            case TypeEnum::BATCH:
                return $this->getAmqpRpcWorkerConfig($node);

            case TypeEnum::BATCH_CONNECTOR:
                return $this->getAmqpRpcWorkerConfig($node);

            case TypeEnum::CRON:
                return $this->getNullWorkerConfig();

            case TypeEnum::SPLITTER:
                return $this->getJsonSplitterConfig();

            default:
                return $this->getHttpWorkerConfig($node);

        }
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getHttpWorkerConfig(Node $node): array
    {
        return [
            'type'     => 'worker.http',
            'settings' => [
                'host'         => $this->hostMapper->getHost(new TypeEnum($node->getType())),
                'process_path' => sprintf(
                    '/%s',
                    $this->hostMapper->getRoute(new TypeEnum($node->getType()), $node->getName())
                ),
                'status_path'  => sprintf(
                    '/%s/test',
                    $this->hostMapper->getRoute(new TypeEnum($node->getType()), $node->getName())
                ),
                'method'       => 'POST',
                'port'         => 80,
                'secure'       => FALSE,
                'opts'         => [],
            ],
        ];
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getAmqpRpcWorkerConfig(Node $node): array
    {
        return [
            'type'     => 'splitter.amqprpc',
            'settings' => [
                'publish_queue' => [
                    'name'    => $node->getType(),
                    'options' => NULL,
                ],
            ],
        ];
    }

    /**
     *
     * @return array
     */
    private function getJsonSplitterConfig(): array
    {
        return [
            'type'     => 'splitter.json',
            'settings' => [],
        ];
    }

    /**
     * @return array
     */
    private function getNullWorkerConfig(): array
    {
        return [
            'type'     => 'worker.null',
            'settings' => [],
        ];
    }

}
