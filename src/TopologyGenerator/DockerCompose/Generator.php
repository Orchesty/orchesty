<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:27 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\CounterServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\NodeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\PhpDevServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\ProbeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\XmlParserServiceBuilder;
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
     * Generator constructor.
     *
     * @param Environment $environment
     * @param HostMapper  $hostMapper
     * @param string      $targetDir
     * @param string      $network
     */
    public function __construct(Environment $environment, HostMapper $hostMapper, string $targetDir, string $network)
    {
        $this->environment    = $environment;
        $this->hostMapper     = $hostMapper;
        $this->targetDir      = $targetDir;
        $this->network        = $network;
        $this->composeBuilder = new ComposeBuilder();
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     *
     * @return string
     */
    public function createTopologyConfig(Topology $topology, iterable $nodes): string
    {
        $config['name'] = GeneratorUtils::normalizeName($topology->getId(), $topology->getName());

        foreach ($nodes as $node) {

            $nodeConfig['id']                 = GeneratorUtils::normalizeName($node->getId(), $node->getName());
            $nodeConfig['worker']['type']     = 'worker.http';
            $nodeConfig['worker']['settings'] = [
                'method' => 'POST',
                'url'    => $this->hostMapper->getUrl(new TypeEnum($node->getType()), $node->getName()),
                'opts'   => [],
            ];

            $nodeConfig['next'] = [];
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
        $compose = new Compose();

        $compose->addNetwork($this->network);

        $builder            = new ProbeServiceBuilder($this->environment, self::REGISTRY, $this->network);
        $nodeWatcherService = $builder->build(new Node());
        $compose->addServices($nodeWatcherService);

        $builder        = new CounterServiceBuilder($this->environment, self::REGISTRY, $this->network);
        $counterService = $builder->build(new Node());
        $compose->addServices($counterService);

        foreach ($nodes as $node) {
            $builder = new NodeServiceBuilder($this->environment, self::REGISTRY, $this->network);
            $compose->addServices($builder->build($node));

            if (HostMapper::isPhpType(new TypeEnum($node->getType()))) {
                $builder       = new PhpDevServiceBuilder(
                    $this->environment,
                    $this->hostMapper,
                    self::REGISTRY,
                    $this->network
                );
                $phpDevService = $builder->build($node);
                $compose->addServices($phpDevService);
            }

            if ($node->getType() === TypeEnum::XML_PARSER) {
                $builder          = new XmlParserServiceBuilder(
                    $this->environment,
                    $this->hostMapper,
                    self::REGISTRY,
                    $this->network
                );
                $xmlParserService = $builder->build(new Node());
                $compose->addServices($xmlParserService);
            }
        }

        return $this->composeBuilder->build($compose);
    }

    /**
     * @param Topology        $topology
     * @param iterable|Node[] $nodes
     */
    public function generate(Topology $topology, iterable $nodes): void
    {
        if (!is_dir($this->getTopologyDir($topology))) {
            mkdir($this->getTopologyDir($topology));
        }

        file_put_contents(
            $this->getTopologyDir($topology) . '/topology.json',
            $this->createTopologyConfig($topology, $nodes)
        );

        file_put_contents(
            $this->getTopologyDir($topology) . '/docker-compose.yml',
            $this->createCompose($topology, $nodes)
        );
    }

    /**
     * @param Topology $topology
     *
     * @return string
     */
    protected function getTopologyDir(Topology $topology): string
    {
        return sprintf(
            '%s/%s',
            $this->targetDir,
            GeneratorUtils::normalizeName($topology->getId(), $topology->getName())
        );
    }

}