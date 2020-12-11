<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository;
use Hanaboso\Utils\String\DsnParser;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class TopologyConfigFactory
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class TopologyConfigFactory
{

    public const ENVIRONMENT            = 'environment';
    public const DOCKER_REGISTRY        = 'docker_registry';
    public const DOCKER_PF_BRIDGE_IMAGE = 'docker_pf_bridge_image';
    public const RABBITMQ_HOST          = 'rabbitmq_host';
    public const RABBITMQ_DSN           = 'rabbitmq_dsn';
    public const RABBITMQ_USER          = 'rabbitmq_user';
    public const RABBITMQ_PASS          = 'rabbitmq_pass';
    public const RABBITMQ_VHOST         = 'rabbitmq_vhost';
    public const MULTI_PROBE_HOST       = 'multi_probe_host';
    public const METRICS_HOST           = 'metrics_host';
    public const METRICS_PORT           = 'metrics_port';
    public const METRICS_SERVICE        = 'metrics_service';
    public const WORKER_DEFAULT_PORT    = 'worker_default_port';
    public const FTP_API_HOST           = 'ftp_api_host';
    public const MAILER_API_HOST        = 'mailer_api_host';
    public const MAPPER_API_HOST        = 'mapper_api_host';
    public const MONOLITH_API_HOST      = 'monolith_api_host';
    public const XML_PARSER_API_HOST    = 'xml_parser_api_host';

    public const NODE_CONFIG             = 'node_config';
    public const WORKER                  = 'worker';
    public const TYPE                    = 'type';
    public const SETTINGS                = 'settings';
    public const FAUCET                  = 'faucet';
    public const PREFETCH                = 'prefetch';
    public const SPLITTER_AMQRPC         = 'splitter.amqprpc';
    public const SPLITTER_AMQRPC_LIMITED = 'splitter.amqprpc_limited';

    public const WORKER_NULL            = 'worker.null';
    public const WORKER_RESEQUENCER     = 'worker.resequencer';
    public const SPLITTER_JSON          = 'splitter.json';
    public const WORKER_HTTP_XML_PARSER = 'worker.http_xml_parser';
    public const WORKER_LONG_RUNNING    = 'worker.long_running';
    public const WORKER_HTTP            = 'worker.http';
    public const WORKER_HTTP_LIMITED    = 'worker.http_limited';
    public const HOST                   = 'host';

    public const PROCESS_PATH  = 'process_path';
    public const STATUS_PATH   = 'status_path';
    public const METHOD        = 'method';
    public const PORT          = 'port';
    public const PUBLISH_QUEUE = 'publish_queue';
    public const NAME          = 'name';

    /**
     * @var mixed[]
     */
    private array $configs;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var ObjectRepository<Node>&NodeRepository
     */
    private NodeRepository $nodeRepo;

    /**
     * TopologyConfigFactory constructor.
     *
     * @param mixed[]         $configs
     * @param DocumentManager $dm
     */
    public function __construct(array $configs, DocumentManager $dm)
    {
        $this->configs = $configs;

        $parsed                              = DsnParser::rabbitParser($configs[self::RABBITMQ_DSN]);
        $this->configs[self::RABBITMQ_HOST]  = sprintf('%s:%s', $parsed[DsnParser::HOST], $parsed[DsnParser::PORT]);
        $this->configs[self::RABBITMQ_VHOST] = $parsed[DsnParser::VHOST] ?? '/';
        $this->configs[self::RABBITMQ_USER]  = $parsed[DsnParser::USER] ?? 'guest';
        $this->configs[self::RABBITMQ_PASS]  = $parsed[DsnParser::PASSWORD] ?? 'guest';

        $this->dm       = $dm;
        $this->nodeRepo = $this->dm->getRepository(Node::class);
    }

    /**
     * @param mixed[] $nodes
     *
     * @return string
     * @throws LockException
     * @throws MappingException
     * @throws TopologyConfigException
     * @throws JsonException
     */
    public function create(array $nodes): string
    {
        $result = [
            self::ENVIRONMENT => $this->getEnvParameters(),
            self::NODE_CONFIG => $this->loopNodes($nodes),
        ];

        return Json::encode($result, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed[] $nodes
     *
     * @return mixed[]
     * @throws TopologyConfigException
     * @throws LockException
     * @throws MappingException
     * @throws JsonException
     */
    private function loopNodes(array $nodes): array
    {
        $arr      = [];
        $nextNode = NULL;
        /** @var Node $node */
        foreach ($nodes as $node) {
            if ($node->getType() === TypeEnum::WEBHOOK) {
                $nextNode = $this->getNextNode($node);
            }

            $arr[$node->getId()] = $this->assembleNode($node, FALSE);
        }
        if ($nextNode) {
            $arr[$nextNode->getId()] = $this->assembleNode($nextNode, TRUE);
        }

        return $arr;
    }

    /**
     * @return mixed[]
     */
    private function getEnvParameters(): array
    {
        return [
            self::DOCKER_REGISTRY        => $this->configs[self::DOCKER_REGISTRY],
            self::DOCKER_PF_BRIDGE_IMAGE => $this->configs[self::DOCKER_PF_BRIDGE_IMAGE],
            self::RABBITMQ_HOST          => $this->configs[self::RABBITMQ_HOST],
            self::RABBITMQ_USER          => $this->configs[self::RABBITMQ_USER],
            self::RABBITMQ_PASS          => $this->configs[self::RABBITMQ_PASS],
            self::RABBITMQ_VHOST         => $this->configs[self::RABBITMQ_VHOST],
            self::MULTI_PROBE_HOST       => $this->configs[self::MULTI_PROBE_HOST],
            self::METRICS_HOST           => $this->configs[self::METRICS_HOST],
            self::METRICS_PORT           => $this->configs[self::METRICS_PORT],
            self::METRICS_SERVICE        => $this->configs[self::METRICS_SERVICE],
            self::WORKER_DEFAULT_PORT    => (int) $this->configs[self::WORKER_DEFAULT_PORT],
        ];
    }

    /**
     * @param Node $node
     *
     * @return mixed[]|null
     * @throws JsonException
     */
    private function getFaucet(Node $node): ?array
    {
        $config = $node->getSystemConfigs();
        if (!$config) {
            return NULL;
        }

        return [
            self::SETTINGS => [
                self::PREFETCH =>
                    $config->getPrefetch(),
            ],
        ];
    }

    /**
     * @param Node $node
     * @param bool $nextConnector
     *
     * @return mixed[]
     * @throws TopologyConfigException
     * @throws JsonException
     */
    private function getWorkers(Node $node, bool $nextConnector): array
    {
        switch ($node->getType()) {
            case sprintf('%s', TypeEnum::WEBHOOK):
            case sprintf('%s', TypeEnum::CRON):
            case sprintf('%s', TypeEnum::START):
                return [
                    self::TYPE => $this->getWorkerByType($node),
                ];
            case sprintf('%s', TypeEnum::BATCH):
            case sprintf('%s', TypeEnum::BATCH_CONNECTOR):
            case sprintf('%s', TypeEnum::RESEQUENCER):
            case sprintf('%s', TypeEnum::SPLITTER):
            case sprintf('%s', TypeEnum::DEBUG):
                return [
                    self::TYPE     => $this->getWorkerByType($node),
                    self::SETTINGS => [
                        self::PUBLISH_QUEUE => $this->getPublishQueue($node->getType()),
                    ],
                ];
            default:
                $path = $this->getPaths($node, $nextConnector);

                return [
                    self::TYPE     => $this->getWorkerByType($node),
                    self::SETTINGS => [
                        self::HOST          => $this->getHost($node->getType(), $node->getSystemConfigs()),
                        self::PROCESS_PATH  => $path[self::PROCESS_PATH],
                        self::STATUS_PATH   => $path[self::STATUS_PATH],
                        self::METHOD        => CurlManager::METHOD_POST,
                        self::PORT          => $this->getPort($node->getType()),
                        self::PUBLISH_QUEUE => $this->getPublishQueue($node->getType()),
                    ],
                ];
        }
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    private function getWorkerByType(Node $node): string
    {
        switch ($node->getType()) {
            case sprintf('%s', TypeEnum::BATCH):
            case sprintf('%s', TypeEnum::BATCH_CONNECTOR):
                $workerType = self::SPLITTER_AMQRPC_LIMITED;

                break;
            case sprintf('%s', TypeEnum::WEBHOOK):
            case sprintf('%s', TypeEnum::GATEWAY):
            case sprintf('%s', TypeEnum::DEBUG):
            case sprintf('%s', TypeEnum::CRON):
            case sprintf('%s', TypeEnum::START):
                $workerType = self::WORKER_NULL;

                break;
            case sprintf('%s', TypeEnum::RESEQUENCER):
                $workerType = self::WORKER_RESEQUENCER;

                break;
            case sprintf('%s', TypeEnum::SPLITTER):
                $workerType = self::SPLITTER_JSON;

                break;
            case sprintf('%s', TypeEnum::XML_PARSER):
                $workerType = self::WORKER_HTTP_XML_PARSER;

                break;
            case sprintf('%s', TypeEnum::USER):
                $workerType = self::WORKER_LONG_RUNNING;

                break;
            default:
                $workerType = self::WORKER_HTTP_LIMITED;
        }

        return $workerType;
    }

    /**
     * @param Node $node
     * @param bool $nextConnector
     *
     * @return mixed[]
     * @throws TopologyConfigException
     */
    private function getPaths(Node $node, bool $nextConnector): array
    {
        switch ($node->getType()) {
            case sprintf('%s', TypeEnum::XML_PARSER):
                $paths = [
                    self::PROCESS_PATH => '/xml_parser',
                    self::STATUS_PATH  => '/xml_parser/test',
                ];

                break;
            case sprintf('%s', TypeEnum::TABLE_PARSER):
                $paths = [
                    self::PROCESS_PATH => sprintf('/parser/json/to/%s/', $node->getName()),
                    self::STATUS_PATH  => sprintf('/parser/json/to/%s/test', $node->getName()),
                ];

                break;
            case sprintf('%s', TypeEnum::FTP):
                $paths = [
                    self::PROCESS_PATH => '/connector/ftp/action',
                    self::STATUS_PATH  => '/connector/ftp/action/test',
                ];

                break;
            case sprintf('%s', TypeEnum::EMAIL):
                $paths = [
                    self::PROCESS_PATH => '/mailer/email',
                    self::STATUS_PATH  => '/mailer/email/test',
                ];

                break;
            case sprintf('%s', TypeEnum::MAPPER):
                $paths = [
                    self::PROCESS_PATH => sprintf('/mapper/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/mapper/%s/test', $node->getName()),
                ];

                break;
            case sprintf('%s', TypeEnum::CONNECTOR):
            case sprintf('%s', TypeEnum::BATCH_CONNECTOR):
                if ($nextConnector) {
                    $paths = [
                        self::PROCESS_PATH => sprintf('/connector/%s/webhook', $node->getName()),
                        self::STATUS_PATH  => sprintf('/connector/%s/webhook/test', $node->getName()),
                    ];

                    break;
                }
                $paths = [
                    self::PROCESS_PATH => sprintf('/connector/%s/action', $node->getName()),
                    self::STATUS_PATH  => sprintf('/connector/%s/action/test', $node->getName()),
                ];

                break;
            case sprintf('%s', TypeEnum::CUSTOM):
                $paths = [
                    self::PROCESS_PATH => sprintf('/custom_node/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/custom_node/%s/process/test', $node->getName()),
                ];

                break;
            case sprintf('%s', TypeEnum::SIGNAL):
                $paths = [
                    self::PROCESS_PATH => '/custom_node/signal/process',
                    self::STATUS_PATH  => '/custom_node/signal/process/test',
                ];

                break;
            case sprintf('%s', TypeEnum::USER):
                $paths = [
                    self::PROCESS_PATH => sprintf('/longRunning/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/longRunning/%s/process/test', $node->getName()),
                ];

                break;
            case sprintf('%s', TypeEnum::API):
                $paths = [
                    self::PROCESS_PATH => '/connector/api/action',
                    self::STATUS_PATH  => '/connector/api/action/test',
                ];

                break;
            default:
                throw new TopologyConfigException(sprintf('Unknown type of routing [%s].', $node->getType()));
        }

        return $paths;
    }

    /**
     * @param string               $nodeType
     * @param SystemConfigDto|null $dto
     *
     * @return string
     * @throws TopologyConfigException
     */
    private function getHost(string $nodeType, ?SystemConfigDto $dto): string
    {
        if ($dto && !empty($dto->getSdkHost())) {
            return $dto->getSdkHost();
        }

        switch ($nodeType) {
            case sprintf('%s', TypeEnum::XML_PARSER):
                $host = $this->configs[self::XML_PARSER_API_HOST];

                break;
            case  sprintf('%s', TypeEnum::FTP):
                $host = $this->configs[self::FTP_API_HOST];

                break;
            case sprintf('%s', TypeEnum::EMAIL):
                $host = $this->configs[self::MAILER_API_HOST];

                break;
            case sprintf('%s', TypeEnum::MAPPER):
                $host = $this->configs[self::MAPPER_API_HOST];

                break;
            case sprintf('%s', TypeEnum::BATCH_CONNECTOR):
            case sprintf('%s', TypeEnum::TABLE_PARSER):
            case sprintf('%s', TypeEnum::CONNECTOR):
            case sprintf('%s', TypeEnum::WEBHOOK):
            case sprintf('%s', TypeEnum::CUSTOM):
            case sprintf('%s', TypeEnum::SIGNAL):
            case sprintf('%s', TypeEnum::USER):
            case sprintf('%s', TypeEnum::API):
                $host = $this->configs[self::MONOLITH_API_HOST];

                break;
            default:
                throw new TopologyConfigException(sprintf('Unknown type of host [%s].', $nodeType));
        }

        return $host;
    }

    /**
     * @param string $nodeType
     *
     * @return mixed[]
     */
    private function getPublishQueue(string $nodeType): array
    {
        switch ($nodeType) {
            case sprintf('%s', TypeEnum::BATCH):
            case sprintf('%s', TypeEnum::BATCH_CONNECTOR):
                return [
                    self::NAME => sprintf('pipes.%s', $nodeType),
                ];
            default:
                return [];
        }
    }

    /**
     * @param string $nodeType
     *
     * @return int
     * @throws TopologyConfigException
     */
    private function getPort(string $nodeType): int
    {
        switch ($nodeType) {
            case sprintf('%s', TypeEnum::API):
            case sprintf('%s', TypeEnum::BATCH):
            case sprintf('%s', TypeEnum::BATCH_CONNECTOR):
            case sprintf('%s', TypeEnum::CONNECTOR):
            case sprintf('%s', TypeEnum::CUSTOM):
            case sprintf('%s', TypeEnum::EMAIL):
            case sprintf('%s', TypeEnum::FTP):
            case sprintf('%s', TypeEnum::MAPPER):
            case sprintf('%s', TypeEnum::SIGNAL):
            case sprintf('%s', TypeEnum::TABLE_PARSER):
            case sprintf('%s', TypeEnum::USER):
            case sprintf('%s', TypeEnum::WEBHOOK):
                return 80;
            default:
                throw new TopologyConfigException(sprintf('Unknown type for port [%s].', $nodeType));
        }
    }

    /**
     * @param Node $node
     *
     * @return Node|null
     * @throws LockException
     * @throws MappingException
     */
    private function getNextNode(Node $node): ?Node
    {
        if ($node->getNext()) {
            /** @var Node $node */
            $node = $this->nodeRepo->find($node->getNext()[0]->getId());

            return $node;
        }

        return NULL;
    }

    /**
     * @param Node $node
     * @param bool $nextConnector
     *
     * @return mixed[]
     * @throws TopologyConfigException
     * @throws JsonException
     */
    private function assembleNode(Node $node, bool $nextConnector): array
    {
        $arr               = [];
        $arr[self::WORKER] = $this->getWorkers($node, $nextConnector);

        if (self::getFaucet($node)) {
            $arr[self::FAUCET] = $this->getFaucet($node);
        }

        return $arr;
    }

}
