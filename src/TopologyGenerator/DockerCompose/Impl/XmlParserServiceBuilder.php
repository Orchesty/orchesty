<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 7.9.17
 * Time: 14:23
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Service;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\ServiceBuilderInterface;
use Hanaboso\PipesFramework\TopologyGenerator\Environment;
use Hanaboso\PipesFramework\TopologyGenerator\HostMapper;

/**
 * Class XmlParserServiceBuilder
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Impl
 */
class XmlParserServiceBuilder implements ServiceBuilderInterface
{

    private const IMAGE = 'python-xml-parser:prod';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $registry;

    /**
     * @var string
     */
    private $network;

    /**
     * @var HostMapper
     */
    private $hostMapper;

    /**
     * NodeServiceBuilder constructor.
     *
     * @param Environment $environment
     * @param HostMapper  $hostMapper
     * @param string      $registry
     * @param string      $network
     */
    public function __construct(Environment $environment, HostMapper $hostMapper, string $registry, string $network)
    {
        $this->environment = $environment;
        $this->registry    = $registry;
        $this->network     = $network;
        $this->hostMapper  = $hostMapper;
    }

    /**
     * @param Node $node
     *
     * @return Service
     */
    public function build(Node $node): Service
    {
        $host    = $this->hostMapper->getHost(new TypeEnum(TypeEnum::XML_PARSER));
        $service = new Service($host);
        $service
            ->setImage($this->registry . '/' . self::IMAGE)
            ->addEnvironment(Environment::XML_PARSER_HOST, $host)
            ->addEnvironment(Environment::XML_PARSER_PORT, $this->environment->getXmlParserPort())
            ->addEnvironment(Environment::XML_PARSER_RELOADED, $this->environment->getXmlParserReloaded())
            ->addEnvironment(Environment::METRICS_HOST, $this->environment->getMetricsHost())
            ->addEnvironment(Environment::METRICS_PORT, $this->environment->getMetricsPort())
            ->addPort('8080:80')
            ->addNetwork($this->network);

        return $service;
    }

}
