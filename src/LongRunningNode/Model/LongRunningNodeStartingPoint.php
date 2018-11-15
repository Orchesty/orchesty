<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use DateTime;
use DateTimeZone;
use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Metrics\SystemMetrics;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\Configurator\StartingPoint\Headers;
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
     *
     * @throws StartingPointException
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
            $headers = $this->createCustomHeaders($doc);

            if ($stop) {
                $headers->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), 1003);
            }

            $this->runTopology($topology, $node, $headers, $this->createBody($body));
        }
    }

    /**
     * @param LongRunningNodeData $document
     *
     * @return Headers
     */
    private function createCustomHeaders(LongRunningNodeData $document): Headers
    {
        $headers = new Headers();
        $headers
            ->addHeader(PipesHeaders::createKey(PipesHeaders::PARENT_ID), $document->getParentId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID), $document->getSequenceId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID), $document->getTopologyId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_NAME), $document->getTopologyName())
            ->addHeader('content-type', $requestHeaders['content-type'][0] ?? 'application/json')
            ->addHeader('timestamp', new DateTime('now', new DateTimeZone('UTC')))
            ->addHeader('delivery-mode', $this->durableMessage ? 2 : 1)
            ->addHeader(
                PipesHeaders::createKey(PipesHeaders::TIMESTAMP),
                (string) SystemMetrics::getCurrentTimestamp()
            )
            ->addHeader(PipesHeaders::createKey(PipesHeaders::PROCESS_ID), $document->getProcessId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::CORRELATION_ID), $document->getCorrelationId())
            ->addHeader(PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER), $document->getId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), 0);

        return $headers;
    }

}