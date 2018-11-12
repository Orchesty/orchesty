<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;

/**
 * Class LongRunningNodeStartingPoint
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Model
 */
class LongRunningNodeStartingPoint extends StartingPoint
{

    /**
     * @var LongRunningNodeManager
     */
    private $nodeManager;

    /**
     * LongRunningNodeStartingPoint constructor.
     *
     * @param BunnyManager           $bunnyManager
     * @param CurlManagerInterface   $curlManager
     * @param InfluxDbSender         $sender
     * @param LongRunningNodeManager $nodeManager
     */
    public function __construct(
        BunnyManager $bunnyManager,
        CurlManagerInterface $curlManager,
        InfluxDbSender $sender,
        LongRunningNodeManager $nodeManager
    )
    {
        parent::__construct($bunnyManager, $curlManager, $sender);
        $this->nodeManager = $nodeManager;
    }

    /**
     * @param Topology    $topology
     * @param Node        $node
     * @param null|string $body
     * @param null|string $token
     * @param bool        $stop
     */
    public function run(
        Topology $topology,
        Node $node,
        ?string $body = NULL,
        ?string $token = NULL,
        bool $stop = FALSE
    ): void
    {
        $doc = $this->nodeManager->getDocument($topology->getId(), $node->getId(), $token);
        if ($doc) {
            $headers = $this->createHeaders($topology);
            $headers->addHeader(PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER), $doc->getId());
            if ($stop) {
                $headers->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), 1003);
            }

            $this->runTopology($topology, $node, $headers, $this->createBody($body));
        }
    }

}