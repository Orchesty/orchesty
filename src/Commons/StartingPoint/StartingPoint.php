<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\Commons\StartingPoint;

use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\StartingPoint\Exception\StartingPointException;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Nette\Utils\Strings;
use Ramsey\Uuid\Uuid;
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
     * @param Topology $topology
     * @param Node     $node
     *
     * @return string
     */
    public function createQueueName(Topology $topology, Node $node): string
    {
        return sprintf(
            'pipes.%s.%s',
            $topology->getId() . '-' . Strings::webalize($topology->getName()),
            $node->getId() . '-' . Strings::webalize($node->getName())
        );
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return bool
     * @throws \Exception
     */
    public function validateTopology(Topology $topology, Node $node): bool
    {
        if ($node->getTopology() !== $topology->getId()) {
            throw new StartingPointException(
                sprintf(
                    'The node[id=%s] does not belong to the topology[id=%s].',
                    $node->getId(),
                    $topology->getId()
                )
            );
        }

        if (!$topology->isEnabled()) {
            throw new StartingPointException(
                sprintf(
                    'The topology[id=%s] does not enable.',
                    $topology->getId()
                )
            );
        }

        if (!$node->isEnabled()) {
            throw new StartingPointException(
                sprintf(
                    'The node[id=%s] does not enable.',
                    $node->getId()
                )
            );
        }

        return TRUE;
    }

    /**
     * @return Headers
     */
    public function createHeaders(): Headers
    {
        $headers = new Headers();
        $headers->addHeader('job_id', Uuid::uuid4()->toString());

        return $headers;
    }

    /**
     * @param Request  $request
     * @param Topology $topology
     * @param Node     $node
     */
    public function runWithRequest(Request $request, Topology $topology, Node $node): void
    {
        $this->runTopology($topology, $node, $this->createHeaders(), [
            'data'     => base64_decode($request->getContent()),
            'settings' => [
                "content-type" => $request->getContentType(),
            ],
        ]);
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     */
    public function run(Topology $topology, Node $node): void
    {
        $this->runTopology($topology, $node, $this->createHeaders(), [
            'data'     => [],
            'settings' => [],
        ]);
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     * @param Headers  $headers
     * @param array    $content
     *
     * @internal param array $data
     */
    protected function runTopology(Topology $topology, Node $node, Headers $headers, array $content = []): void
    {
        $this->validateTopology($topology, $node);
        $this->startingPointProducer
            ->getManager()
            ->getChannel()
            ->queueDeclare($this->createQueueName($topology, $node));

        $this->startingPointProducer->publish(
            $content,
            $this->createQueueName($topology, $node),
            $headers->getHeaders()
        );
    }

}