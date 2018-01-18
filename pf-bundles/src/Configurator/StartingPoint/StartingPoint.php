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
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Commons\Transport\Utils\TransportFormatter;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * StartingPoint constructor.
     *
     * @param BunnyManager         $bunnyManager
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(BunnyManager $bunnyManager, CurlManagerInterface $curlManager)
    {
        $this->bunnyManager = $bunnyManager;
        $this->curlManager  = $curlManager;
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
     * @param Topology $topology
     * @param Node     $node
     *
     * @return string
     */
    public static function createQueueName(Topology $topology, Node $node): string
    {
        return sprintf(
            self::QUEUE_PATTERN,
            GeneratorUtils::createNormalizedServiceName($topology->getId(), $topology->getName()),
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
     * @throws Exception
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
     */
    public function createHeaders(Topology $topology, array $requestHeaders = []): Headers
    {
        $headers = new Headers();
        $headers
            ->addHeader(PipesHeaders::createKey(PipesHeaders::PROCESS_ID), Uuid::uuid4()->toString())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::PARENT_ID), '')
            ->addHeader(PipesHeaders::createKey(PipesHeaders::CORRELATION_ID), Uuid::uuid4()->toString())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::SEQUENCE_ID), '1')
            ->addHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID), $topology->getId())
            ->addHeader(PipesHeaders::createKey(PipesHeaders::TOPOLOGY_NAME), $topology->getName())
            ->addHeader('content-type', $requestHeaders['content-type'][0] ?? 'application/json')
            ->addHeader('timestamp', new DateTime('now', new DateTimeZone('UTC')));

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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    protected function runTopology(Topology $topology, Node $node, Headers $headers, string $content = ''): void
    {
        $this->validateTopology($topology, $node);

        // Create channel and queues
        /** @var Channel $channel */
        $channel = $this->bunnyManager->getChannel();
        $channel->queueDeclare(self::createQueueName($topology, $node), FALSE, TRUE);
        $channel->queueDeclare(self::createCounterQueueName(), FALSE, TRUE);

        $this->logger->info('Starting point info message', [
            'correlation_id' => PipesHeaders::get(PipesHeaders::CORRELATION_ID, $headers->getHeaders()),
            'process_id'     => PipesHeaders::get(PipesHeaders::PROCESS_ID, $headers->getHeaders()),
            'parent_id'      => PipesHeaders::get(PipesHeaders::PARENT_ID, $headers->getHeaders()),
            'node_id'        => $node->getId(),
            'node_name'      => $node->getName(),
            'topology_id'    => $topology->getId(),
            'topology_name'  => $topology->getName(),
            'type'           => 'starting_point',
        ]);

        // Publish messages
        $this->publishInitializeCounterProcess($channel, self::createCounterQueueName(), $headers, $node);
        $this->publishProcessMessage($channel, self::createQueueName($topology, $node), $headers, $content);
    }

    /**
     * @param Topology $topology
     *
     * @return array
     * @throws StartingPointException
     */
    public function runTest(Topology $topology): array
    {
        $uri = sprintf('multi-probe:%s/topology/status?topologyId=%s', 8007, $topology->getId());

        $requestDto = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));

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
        $this->logger->info(
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
     * @param Node    $node
     */
    private function publishInitializeCounterProcess(Channel $channel, string $queue, Headers $headers,
                                                     Node $node): void
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
                PipesHeaders::createKey(PipesHeaders::NODE_ID)   => $node->getId(),
                PipesHeaders::createKey(PipesHeaders::NODE_NAME) => $node->getName(),
            ]
        );
        $content = json_encode($content);

        $channel->publish($content, $headers, '', $queue);
        $this->logger->info(
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
    private function publishProcessMessage(Channel $channel, string $queue, Headers $headers,
                                           string $content = ''): void
    {
        $channel->publish(
            $content,
            $headers->getHeaders(),
            '',
            $queue
        );
        $this->logger->info(
            'Starting point - publish process message',
            array_merge(
                $this->prepareMessage($content, '', $queue, $headers->getHeaders()),
                PipesHeaders::debugInfo($headers->getHeaders())
            )
        );
    }

}