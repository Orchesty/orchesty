<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\Commons\StartingPoint;

use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StartingPoint
 *
 * @package Hanaboso\PipesFramework\Commons
 */
class StartingPoint
{

    /**
     * @var StartingPointProducer
     */
    private $startingPointProducer;

    /**
     * StartingPoint constructor.
     *
     * @param StartingPointProducer $startingPointProducer
     */
    public function __construct(StartingPointProducer $startingPointProducer)
    {
        $this->startingPointProducer = $startingPointProducer;
    }

    /**
     * @param Request  $request
     * @param Topology $topology
     * @param Node     $node
     */
    public function runWithRequest(Request $request, Topology $topology, Node $node): void
    {
        $this->startingPointProducer->getManager()->getChannel()->queueDeclare('abc');

        $this->startingPointProducer->publish([], 'abc');
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     */
    public function run(Topology $topology, Node $node): void
    {
        $this->startingPointProducer->getManager()->getChannel()->queueDeclare('abc');

        $this->startingPointProducer->publish([], 'abc');
    }

}