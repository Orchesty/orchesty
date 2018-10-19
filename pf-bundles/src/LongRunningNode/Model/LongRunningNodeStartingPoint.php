<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
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
     *
     * @throws StartingPointException
     */
    public function run(Topology $topology, Node $node, ?string $body = NULL): void
    {
        $doc     = $this->nodeManager->getDocument($topology->getId(), $node->getId());
        $headers = $this->createHeaders($topology);
        $headers->addHeader(PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER), $doc->getId());
        $headers->addHeader(PipesHeaders::createKey(LongRunningNodeData::UPDATED_BY_HEADER), $doc->getUpdatedBy());
        $headers->addHeader(PipesHeaders::createKey(LongRunningNodeData::AUDIT_LOGS_HEADER), $doc->getAuditLogs());
        $headers->addHeader(PipesHeaders::createKey(LongRunningNodeData::UPDATED_HEADER),
            $doc->getUpdated()->format('Y-m-d H:i:s'));
        $headers->addHeader(PipesHeaders::createKey(LongRunningNodeData::CREATED_HEADER),
            $doc->getCreated()->format('Y-m-d H:i:s'));

        $this->runTopology($topology, $node, $headers, $this->createBody($body));
    }

}