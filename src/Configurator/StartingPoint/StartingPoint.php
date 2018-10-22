<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\Configurator\StartingPoint;

use Bunny\Channel;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\MetricsEnum;
use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Metrics\SystemMetrics;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Transport\Utils\TransportFormatter;
use Hanaboso\CommonsBundle\Utils\CurlMetricUtils;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\Utils\GeneratorUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class StartingPoint
 *
 * @package Hanaboso\PipesFramework\Configurator\StartingPoint
 */
class StartingPoint implements LoggerAwareInterface
{

    use DebugMessageTrait;

    private const EXCHANGE_PATTERN = 'pipes.%s.events';

    private const QUEUE_PATTERN = 'pipes.%s.%s';

    private const COUNTER_MESSAGE_TYPE = 'counter_message';

    /**
     * @var BunnyManager
     */
    private $bunnyManager;

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var InfluxDbSender
     */
    private $sender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $durableQueue = FALSE;

    /**
     * @var bool
     */
    private $durableMessage = FALSE;

    /**
     * StartingPoint constructor.
     *
     * @param BunnyManager         $bunnyManager
     * @param CurlManagerInterface $curlManager
     * @param InfluxDbSender       $sender
     */
    public function __construct(BunnyManager $bunnyManager, CurlManagerInterface $curlManager, InfluxDbSender $sender)
    {
        $this->bunnyManager = $bunnyManager;
        $this->curlManager  = $curlManager;
        $this->sender       = $sender;
        $this->logger       = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param bool $durableQueue
     */
    public function setDurableQueue(bool $durableQueue): void
    {
        $this->durableQueue = $durableQueue;
    }

    /**
     * @param bool $durableMessage
     */
    public function setDurableMessage(bool $durableMessage): void
    {
        $this->durableMessage = $durableMessage;
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return string
     */
    public static function createQueueName(Topology $topology, Node $node): string
    {
        return sprintf(
            self::QUEUE_PATTERN,
            $topology->getId(),
            GeneratorUtils::createNormalizedServiceName($node->getId(), $node->getName())
        );
    }

    /**
     * @return string
     */
    public static function createCounterQueueName(): string
    {
        return 'pipes.multi-counter';
    }

    /**
     * @param Topology $topology
     *
     * @return string
     */
    public static function createExchangeName(Topology $topology): string
    {
        return sprintf(
            self::EXCHANGE_PATTERN,
            GeneratorUtils::createNormalizedServiceName($topology->getId(), $topology->getName())
        );
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return bool
     * @throws StartingPointException
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
     * @param Topology $topology
     * @param array    $requestHeaders
     *
     * @return Headers
     * @throws StartingPointException
     */
    public function createHeaders(Topology $topology, array $requestHeaders = []): Headers
    {
        $headers = new Headers();
        $headers
            ->addHeader(PipesHeaders::createKey(PipesHeaders::PARENT_ID), '')
            ->addHeader(PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID), '1')
            ->addHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID), $topology->getId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_NAME), $topology->getName())
            ->addHeader('content-type', $requestHeaders['content-type'][0] ?? 'application/json')
            ->addHeader('timestamp', new DateTime('now', new DateTimeZone('UTC')))
            ->addHeader('delivery-mode', $this->durableMessage ? 2 : 1)
            ->addHeader(
                PipesHeaders::createKey(PipesHeaders::TIMESTAMP),
                (string) SystemMetrics::getCurrentTimestamp()
            );

        try {
            $headers
                ->addHeader(PipesHeaders::createKey(PipesHeaders::PROCESS_ID), Uuid::uuid4()->toString())
                ->addHeader(PipesHeaders::createKey(PipesHeaders::CORRELATION_ID), Uuid::uuid4()->toString());
        } catch (Throwable $t) {
            throw new StartingPointException($t->getMessage(), $t->getCode(), $t->getPrevious());
        }

        foreach (PipesHeaders::clear($requestHeaders) as $key => $value) {
            $headers->addHeader($key, (string) $value[0]);
        }

        return $headers;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function createBodyFromRequest(Request $request): string
    {
        $content = '{}';
        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            /** @var string $content */
            $content = $request->getContent();
        }

        return $content;
    }

    /**
     * @param null|string $body JSON string
     *
     * @return string
     */
    public function createBody(?string $body = NULL): string
    {
        return $body ?? '';
    }

    /**
     * @param Request  $request
     * @param Topology $topology
     * @param Node     $node
     *
     * @throws StartingPointException
     */
    public function runWithRequest(Request $request, Topology $topology, Node $node): void
    {
        $this->logInputRequest($request);
        $this->runTopology(
            $topology,
            $node,
            $this->createHeaders($topology, $request->headers->all()),
            $this->createBodyFromRequest($request)
        );
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
        $this->runTopology($topology, $node, $this->createHeaders($topology), $this->createBody($body));
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     * @param Headers  $headers
     * @param string   $content
     *
     * @throws StartingPointException
     */
    protected function runTopology(Topology $topology, Node $node, Headers $headers, string $content = ''): void
    {
        $currentMetrics = $this->startMetrics();
        $this->validateTopology($topology, $node);

        try {
            // Create channel and queues
            /** @var Channel $channel */
            $channel = $this->bunnyManager->getChannel();
            $channel->queueDeclare(self::createQueueName($topology, $node), FALSE, $this->durableQueue);
            $channel->queueDeclare(self::createCounterQueueName(), FALSE, $this->durableQueue);
        } catch (Throwable $t) {
            throw  new StartingPointException($t->getMessage(), $t->getCode(), $t->getPrevious());
        }

        $correlation_id = PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers->getHeaders());
        $this->logger->debug('Starting point info message', [
            'correlation_id' => $correlation_id,
            'process_id'     => PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers->getHeaders()),
            'parent_id'      => PipesHeaders::get(PipesHeaders::PARENT_ID, $headers->getHeaders()),
            'node_id'        => $node->getId(),
            'node_name'      => $node->getName(),
            'topology_id'    => $topology->getId(),
            'topology_name'  => $topology->getName(),
            'type'           => 'starting_point',
        ]);

        // Publish messages
        $this->publishInitializeCounterProcess($channel, self::createCounterQueueName(), $headers);
        $this->publishProcessMessage($channel, self::createQueueName($topology, $node), $headers, $content);
        $this->sendMetrics($currentMetrics, $correlation_id, $topology, $node);
    }

    /**
     * @param Topology $topology
     *
     * @return array
     * @throws StartingPointException
     * @throws CurlException
     */
    public function runTest(Topology $topology): array
    {
        $uri = sprintf('multi-probe:%s/topology/status?topologyId=%s', 8007, $topology->getId());

        $requestDto  = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));
        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            $data = json_decode($responseDto->getBody(), TRUE);

            return $data;
        } else {
            throw new StartingPointException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }
    }

    /**
     * @param Request $request
     */
    private function logInputRequest(Request $request): void
    {
        /** @var string $content */
        $content = $request->getContent();
        $this->logger->debug(
            TransportFormatter::requestToString(
                $request->getMethod(), $request->getUri(), $request->headers->all(), $content
            ),
            PipesHeaders::debugInfo($request->headers->all())
        );
    }

    /**
     * @param Channel $channel
     * @param string  $queue
     * @param Headers $headers
     */
    private function publishInitializeCounterProcess(
        Channel $channel,
        string $queue,
        Headers $headers
    ): void
    {
        $content = [
            'result' => [
                'code'    => 0,
                'message' => 'Starting point started process',
            ],
            'route'  => [
                'following'  => 1,
                'multiplier' => 1,
            ],
        ];

        $headers = array_merge(
            $headers->getHeaders(),
            [
                'type'                                           => self::COUNTER_MESSAGE_TYPE,
                'app_id'                                         => 'starting_point',
                PipesHeaders::createKey(PipesHeaders::NODE_ID)   => 'starting_point',
                PipesHeaders::createKey(PipesHeaders::NODE_NAME) => 'starting_point',
            ]
        );
        $content = json_encode($content);

        $channel->publish($content, $headers, '', $queue);
        $this->logger->debug(
            'Starting point - publish counter message',
            array_merge(
                $this->prepareMessage($content, '', $queue, $headers),
                PipesHeaders::debugInfo($headers)
            )
        );
    }

    /**
     * @param Channel $channel
     * @param string  $queue
     * @param Headers $headers
     * @param string  $content
     */
    private function publishProcessMessage(
        Channel $channel,
        string $queue,
        Headers $headers,
        string $content = ''
    ): void
    {
        $channel->publish(
            $content,
            $headers->getHeaders(),
            '',
            $queue
        );
        $this->logger->debug(
            'Starting point - publish process message',
            array_merge(
                $this->prepareMessage($content, '', $queue, $headers->getHeaders()),
                PipesHeaders::debugInfo($headers->getHeaders())
            )
        );
    }

    /**
     * @return array
     */
    private function startMetrics(): array
    {
        return CurlMetricUtils::getCurrentMetrics();
    }

    /**
     * @param array    $currentMetrics
     * @param string   $correlationId
     * @param Topology $topology
     * @param Node     $node
     */
    private function sendMetrics(array $currentMetrics, string $correlationId, Topology $topology, Node $node): void
    {
        $times = CurlMetricUtils::getTimes($currentMetrics);

        $this->sender->send(
            [
                MetricsEnum::REQUEST_TOTAL_DURATION => $times[CurlMetricUtils::KEY_REQUEST_DURATION],
                MetricsEnum::CPU_USER_TIME          => $times[CurlMetricUtils::KEY_USER_TIME],
                MetricsEnum::CPU_KERNEL_TIME        => $times[CurlMetricUtils::KEY_KERNEL_TIME],
            ],
            [
                MetricsEnum::HOST           => gethostname(),
                MetricsEnum::TOPOLOGY_ID    => $topology->getId(),
                MetricsEnum::CORRELATION_ID => $correlationId,
                MetricsEnum::NODE_ID        => $node->getId(),
            ]
        );
    }

}
