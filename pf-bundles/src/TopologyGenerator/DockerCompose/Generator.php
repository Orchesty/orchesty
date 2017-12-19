<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:27 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Exception;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\MultiNodeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\NodeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\ServiceTrait;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorInterface;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use Hanaboso\PipesFramework\TopologyGenerator\HostMapper;
use stdClass;

/**
 * Class Generator
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class Generator implements GeneratorInterface
{

    use ServiceTrait;

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
    private $multiModeEnabled = FALSE;

    /**
     * @var string
     */
    private $topologyPrefix;

    /**
     * @var string
     */
    private $topologyMode;

    /**
     * Generator constructor.
     *
     * @param Environment                 $environment
     * @param HostMapper                  $hostMapper
     * @param string                      $targetDir
     * @param string                      $network
     * @param VolumePathDefinitionFactory $volumePathDefinitionFactory
     * @param string                      $topologyPrefix
     * @param string                      $topologyMode
     */
    public function __construct(
        Environment $environment,
        HostMapper $hostMapper,
        string $targetDir,
        string $network,
        VolumePathDefinitionFactory $volumePathDefinitionFactory,
        string $topologyPrefix,
        string $topologyMode
    )
    {
        $this->environment                 = $environment;
        $this->hostMapper                  = $hostMapper;
        $this->targetDir                   = $targetDir;
        $this->network                     = $network;
        $this->composeBuilder              = new ComposeBuilder();
        $this->volumePathDefinitionFactory = $volumePathDefinitionFactory;
        $this->topologyPrefix              = $topologyPrefix;
        $this->topologyMode                = $topologyMode;
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     *
     * @throws Exception
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
     * @param bool $enable
     */
    public function setMultiMode(bool $enable): void
    {
        $this->multiModeEnabled = $enable;
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     *
     * @return string
     * @throws Exception
     */
    public function createTopologyConfig(Topology $topology, iterable $nodes): string
    {
        $config['id']            = GeneratorUtils::createNormalizedServiceName($topology->getId(),
            $topology->getName());
        $config['topology_id']   = $topology->getId();
        $config['topology_name'] = $topology->getName();

        $i           = 0;
        $defaultPort = 8008;
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
                $nodeConfig['next'][] = GeneratorUtils::createNormalizedServiceName($next->getId(), $next->getName());
            }

            if ($this->multiModeEnabled) {
                $multiName           = $this->getMultiNodeName($topology);
                $port                = $defaultPort + $i;
                $nodeConfig['debug'] = [
                    'port' => $port,
                    'host' => $multiName,
                    'url'  => sprintf('http://%s:%s/status', $multiName, $port),
                ];
            }

            $config['nodes'][] = $nodeConfig;
            $i++;
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
        $volume = $this->volumePathDefinitionFactory->create($topology);

        $compose = new Compose();

        $compose->addNetwork($this->network);

        $this->addBridges($compose, $topology, $nodes, $volume);

        return $this->composeBuilder->build($compose);
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
        if ($this->multiModeEnabled) {
            // Run all topology bridges is single container
            $builder = new MultiNodeServiceBuilder(
                $this->getMultiNodeName($topology),
                $this->environment,
                self::REGISTRY,
                $this->network,
                $volumePD,
                $this->topologyMode,
                $this->topologyPrefix
            );

            $multi = $builder->build(new Node());
            $compose->addService($multi);
        } else {
            // Run every bridge in dedicated container
            foreach ($nodes as $node) {
                $builder = new NodeServiceBuilder(
                    $this->environment,
                    self::REGISTRY,
                    $this->network,
                    $volumePD,
                    $this->topologyPrefix,
                    $this->topologyMode
                );
                $compose->addService($builder->build($node));
            }
        }
    }

    /**
     * @param Topology $topology
     *
     * @return string
     */
    private function getMultiNodeName(Topology $topology): string
    {
        return sprintf('%s_mb', $topology->getId());
    }

    /**
     * @param Node $node
     *
     * @return array
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
            'settings' => new stdClass(),
        ];
    }

    /**
     * @return array
     */
    private function getNullWorkerConfig(): array
    {
        return [
            'type'     => 'worker.null',
            'settings' => new stdClass(),
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
            'settings' => new stdClass(),
        ];
    }

}
