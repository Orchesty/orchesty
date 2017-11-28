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
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\MultiNodeServiceBuilder;
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
     * @var boolean
     */
    private $bridgesInSeparateContainers = TRUE;

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
     * @param bool $bridgesInSeparateContainers
     */
    public function setBridgesInSeparateContainers(bool $bridgesInSeparateContainers): void
    {
        $this->bridgesInSeparateContainers = $bridgesInSeparateContainers;
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     *
     * @return string
     */
    public function createTopologyConfig(Topology $topology, iterable $nodes): string
    {
        $config['id']            = GeneratorUtils::createServiceName(
            GeneratorUtils::normalizeName($topology->getId(), $topology->getName())
        );
        $config['topology_id']   = $topology->getId();
        $config['topology_name'] = $topology->getName();

        foreach ($nodes as $node) {
            $nodeFullId = GeneratorUtils::normalizeName($node->getId(), $node->getName());

            $nodeConfig           = [];
            $nodeConfig['id']     = GeneratorUtils::createServiceName($nodeFullId);
            $nodeConfig['label']  = [
                'id'        => GeneratorUtils::createServiceName($nodeFullId),
                'node_id'   => $node->getId(),
                'node_name' => $node->getName(),
            ];
            $nodeConfig['worker'] = $this->getWorkerConfig($node);
            $nodeConfig['next']   = [];
            foreach ($node->getNext() as $next) {
                $nodeConfig['next'][] = GeneratorUtils::createServiceName(
                    GeneratorUtils::normalizeName($next->getId(), $next->getName())
                );
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
            $topology,
            $volumePathDefinition
        );

        $counterService = $builder->build(new Node());
        $compose->addServices($counterService);

        $this->addBridges($compose, $topology, $nodes, $volumePathDefinition);

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
     * @param Compose              $compose
     * @param Topology             $topology
     * @param iterable             $nodes
     * @param VolumePathDefinition $volumePD
     */
    private function addBridges(
        Compose $compose,
        Topology $topology,
        iterable $nodes,
        VolumePathDefinition $volumePD
    ): void
    {
        if ($this->bridgesInSeparateContainers) {
            // Run every bridge in dedicated container
            foreach ($nodes as $node) {
                $builder = new NodeServiceBuilder(
                    $this->environment,
                    self::REGISTRY,
                    $this->network,
                    $volumePD
                );
                $compose->addServices($builder->build($node));
            }
        } else {
            // Run all topology bridges is single container
            $builder = new MultiNodeServiceBuilder(
                $topology,
                $this->environment,
                self::REGISTRY,
                $this->network,
                $volumePD
            );

            $compose->addServices($builder->build(new Node()));
        }
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

            case TypeEnum::DEBUG:
                return $this->getNullWorkerConfig();

            case TypeEnum::RESEQUENCER:
                return $this->getResequencerWorkerConfig();

            case TypeEnum::SPLITTER:
                return $this->getJsonSplitterConfig();

            case TypeEnum::XML_PARSER:
                return $this->getHttpXmlParserWorkerConfig($node);

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
            'settings' => $this->getHttpWorkerSettings($node),
        ];
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getHttpXmlParserWorkerConfig(Node $node): array
    {
        $config = [
            'type'     => 'worker.http_xml_parser',
            'settings' => $this->getHttpWorkerSettings($node),
        ];

        // @TODO - doplnit do parser_settings: type, file, content
        $config['settings']['parser_settings'] = [];

        return $config;
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getHttpWorkerSettings(Node $node): array
    {
        return [
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
                    'name'    => sprintf('pipes.%s', $node->getType()),
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

    /**
     *
     * @return array
     */
    private function getResequencerWorkerConfig(): array
    {
        return [
            'type'     => 'worker.resequencer',
            'settings' => [],
        ];
    }

}
