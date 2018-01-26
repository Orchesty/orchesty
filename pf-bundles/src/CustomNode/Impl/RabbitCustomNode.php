<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;

/**
 * Class RabbitCustomNode
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class RabbitCustomNode implements CustomNodeInterface
{

    /**
     * @var AbstractProducer
     */
    private $producer;

    /**
     * @var ObjectRepository|NodeRepository
     */
    private $nodeRepo;

    /**
     * RabbitCustomNode constructor.
     *
     * @param DocumentManager  $dm
     * @param AbstractProducer $producer
     */
    public function __construct(DocumentManager $dm, AbstractProducer $producer)
    {
        $this->producer = $producer;
        $this->nodeRepo = $dm->getRepository(Node::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data  = json_decode($dto->getData(), TRUE);
        $count = intval($data['count'] ?? 10);

        $topId  = PipesHeaders::get(PipesHeaders::TOPOLOGY_ID, $dto->getHeaders());
        $nodeId = PipesHeaders::get(PipesHeaders::NODE_ID, $dto->getHeaders());

        $ex    = $this->producer->getExchange();
        $chann = $this->producer->getManager()->getChannel();

        /** @var Node $node */
        $node = $this->nodeRepo->find($nodeId);
        $ques = [];

        /** @var EmbedNode $next */
        foreach ($node->getNext() as $next) {
            $que    = GeneratorUtils::generateQueueNameFromStrings($topId, $next->getId(), $next->getName());
            $ques[] = $que;

            $chann->queueBind($que, $ex, $que);
        }

        for ($i = 0; $i < $count; $i++) {
            $msg = sprintf('{"BenchmarkTotal": %s, "BenchmarkNumber": %s}', $count, $i);

            foreach ($ques as $que) {
                $chann->publish($msg, [$dto->getHeaders()], $ex, $que);
            }
        }

        foreach ($ques as $que) {
            $chann->queueUnbind($que, $ex, $que);
        }

        return $dto;
    }

}