<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:07 PM
 */

namespace Hanaboso\PipesFramework\Configurator\StartingPoint;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\StartingPointException;
use Nette\Utils\Strings;
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

    private const CONTENT = '{"data":%s, "settings": ""}';

    /**
     * @var StartingPointProducer
     */
    private $startingPointProducer;

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
     * @param StartingPointProducer $startingPointProducer
     * @param CurlManagerInterface  $curlManager
     */
    public function __construct(StartingPointProducer $startingPointProducer, CurlManagerInterface $curlManager)
    {
        $this->startingPointProducer = $startingPointProducer;
        $this->curlManager           = $curlManager;
        $this->logger                = new NullLogger();
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
        $headers
            ->addHeader('process_id', Uuid::uuid4()->toString())
            ->addHeader('correlation_id', Uuid::uuid4()->toString())
            ->addHeader('sequence_id', '1');

        return $headers;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function createBodyFromRequest(Request $request): string
    {
        if ($request->getContentType() === 'json') {
            return sprintf(self::CONTENT, $request->getContent());
        } else {
            return sprintf(self::CONTENT, json_encode($request->getContent()));
        }
    }

    /**
     * @param null|string $param
     *
     * @return string
     */
    public function createBody(?string $param = NULL): string
    {
        return sprintf(self::CONTENT, $param ?? '""');
    }

    /**
     * @param Request  $request
     * @param Topology $topology
     * @param Node     $node
     */
    public function runWithRequest(Request $request, Topology $topology, Node $node): void
    {
        $this->runTopology($topology, $node, $this->createHeaders(), $this->createBodyFromRequest($request));
    }

    /**
     * @param Topology    $topology
     * @param Node        $node
     * @param null|string $param
     */
    public function run(Topology $topology, Node $node, ?string $param = NULL): void
    {
        $this->runTopology($topology, $node, $this->createHeaders(), $this->createBody($param));
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     * @param Headers  $headers
     * @param string   $content
     *
     * @internal param array $data
     */
    protected function runTopology(Topology $topology, Node $node, Headers $headers, string $content = ''): void
    {
        $this->validateTopology($topology, $node);
        $this->startingPointProducer
            ->getManager()
            ->getChannel()
            ->queueDeclare($this->createQueueName($topology, $node), FALSE, TRUE);

        $headers = $headers->getHeaders();
        $this->startingPointProducer->publish(
            $content,
            $this->createQueueName($topology, $node),
            $headers
        );
        $this->logger->info('Starting point message', [
            'correlation_id' => $headers['correlation_id'],
            'process_id'     => $headers['process_id'],
            'node_id'        => $node->getId(),
            'node_name'      => $node->getName(),
            'topology_id'    => $topology->getId(),
            'topology_name'  => $topology->getName(),
            'type'           => 'starting_point',
        ]);
    }

    /**
     * @param Topology $topology
     *
     * @return array
     * @throws StartingPointException
     */
    public function runTest(Topology $topology): array
    {
        $uri = sprintf('%s_probe:%s/status', $topology->getId(), 8007);

        $requestDto = new RequestDto(CurlManager::METHOD_GET, new Uri($uri));

        $responseDto = $this->curlManager->send($requestDto);

        if ($responseDto->getStatusCode() === 200) {
            return json_decode($responseDto->getBody(), TRUE);
        } else {
            throw new StartingPointException(sprintf('Request error: %s', $responseDto->getReasonPhrase()));
        }
    }

}