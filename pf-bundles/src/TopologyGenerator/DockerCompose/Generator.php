<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 11:27 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose;

use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\CounterServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\NodeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\ProbeServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl\XmlParserServiceBuilder;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorInterface;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

/**
 * Class Generator
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class Generator implements GeneratorInterface
{

    public const REGISTRY = 'dkr.hanaboso.net/pipes/pipes';
    public const NETWORK  = 'demo_default';

    /**
     * @var Topology
     */
    private $topology;

    /**
     * @var iterable|Node[]
     */
    private $nodes;

    /**
     * @var ComposeBuilder
     */
    private $composeBuilder;

    /**
     * Generator constructor.
     *
     * @param Topology $topology
     * @param iterable $nodes
     */
    public function __construct(Topology $topology, iterable $nodes)
    {
        $this->topology       = $topology;
        $this->nodes          = $nodes;
        $this->composeBuilder = new ComposeBuilder();
    }

    /**
     * @return string
     */
    public function createTopologyConfig(): string
    {
        $config['name'] = GeneratorUtils::normalizeName($this->topology->getId(), $this->topology->getName());

        foreach ($this->nodes as $node) {
            $nodeConfig['id']                 = GeneratorUtils::normalizeName($node->getId(), $node->getName());
            $nodeConfig['worker']['type']     = 'worker.http';
            $nodeConfig['worker']['settings'] = [
                'method' => 'POST',
                'url'    => 'backend',
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
     * @return string
     */
    public function createCompose(): string
    {
        $compose     = new Compose();
        $environment = new Environment();

        $compose->addNetwork(self::NETWORK);

        $builder            = new ProbeServiceBuilder($environment, self::REGISTRY, self::NETWORK);
        $nodeWatcherService = $builder->build(new Node());
        $compose->addServices($nodeWatcherService);

        $builder        = new CounterServiceBuilder($environment, self::REGISTRY, self::NETWORK);
        $counterService = $builder->build(new Node());
        $compose->addServices($counterService);

        $builder        = new XmlParserServiceBuilder($environment, self::REGISTRY, self::NETWORK);
        $counterService = $builder->build(new Node());
        $compose->addServices($counterService);

        foreach ($this->nodes as $node) {
            $builder = new NodeServiceBuilder($environment, self::REGISTRY, self::NETWORK);
            $compose->addServices($builder->build($node));
        }

        return $this->composeBuilder->build($compose);
    }

    /**
     * @param string $targetDir
     */
    public function generate(string $targetDir): void
    {
        if (!is_dir($this->getTopologyDir($targetDir))) {
            mkdir($this->getTopologyDir($targetDir));
        }

        file_put_contents($this->getTopologyDir($targetDir) . '/topology.json', $this->createTopologyConfig());

        file_put_contents($this->getTopologyDir($targetDir) . '/docker-compose.yml', $this->createCompose());
    }

    /**
     * @param string $targetDir
     *
     * @return string
     */
    protected function getTopologyDir(string $targetDir): string
    {
        return sprintf(
            '%s/%s',
            $targetDir,
            GeneratorUtils::normalizeName($this->topology->getId(), $this->topology->getName())
        );
    }

}