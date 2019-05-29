<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfigDto;

/**
 * Class TopologyConfigFactory
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
class TopologyConfigFactory
{

    public const ENVIRONMENT            = 'environment';
    public const DOCKER_REGISTRY        = 'docker_registry';
    public const DOCKER_PF_BRIDGE_IMAGE = 'docker_pf_bridge_image';
    public const RABBITMQ_HOST          = 'rabbitmq_host';
    public const RABBITMQ_USER          = 'rabbitmq_user';
    public const RABBITMQ_PASS          = 'rabbitmq_pass';
    public const RABBITMQ_VHOST         = 'rabbitmq_vhost';
    public const MULTI_PROBE_HOST       = 'multi_probe_host';
    public const METRICS_HOST           = 'metrics_host';
    public const WORKER_DEFAULT_PORT    = 'worker_default_port';
    public const FTP_API_HOST           = 'ftp_api_host';
    public const MAILER_API_HOST        = 'mailer_api_host';
    public const MAPPER_API_HOST        = 'mapper_api_host';
    public const MONOLITH_API_HOST      = 'monolith_api_host';
    public const XML_PARSER_API_HOST    = 'xml_parser_api_host';

    public const NODE_CONFIG     = 'node_config';
    public const WORKER          = 'worker';
    public const TYPE            = 'type';
    public const SETTINGS        = 'settings';
    public const FAUCET          = 'faucet';
    public const PREFETCH        = 'prefetch';
    public const SPLITTER_AMQRPC = 'splitter.amqprpc';

    public const WORKER_NULL            = 'worker.null';
    public const WORKER_RESEQUENCER     = 'worker.resequencer';
    public const SPLITTER_JSON          = 'splitter.json';
    public const WORKER_HTTP_XML_PARSER = 'worker.http_xml_parser';
    public const WORKER_LONG_RUNNING    = 'worker.long_running';
    public const WORKER_HTTP            = 'worker.http';
    public const HOST                   = 'host';

    public const PROCESS_PATH  = 'process_path';
    public const STATUS_PATH   = 'status_path';
    public const METHOD        = 'method';
    public const PORT          = 'port';
    public const PUBLISH_QUEUE = 'publish_queue';
    public const NAME          = 'name';

    /**
     * @var array
     */
    private $configs;

    /**
     * TopologyConfigFactory constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param array $nodes
     *
     * @return string
     * @throws TopologyConfigException
     */
    public function create(array $nodes): string
    {
        $result = [
            self::ENVIRONMENT => $this->getEnvParameters(),
            self::NODE_CONFIG => $this->loopNodes($nodes),
        ];

        return (string) json_encode($result, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array $nodes
     *
     * @return array
     * @throws TopologyConfigException
     */
    private function loopNodes(array $nodes): array
    {
        $arr = [];
        /** @var Node $node */
        foreach ($nodes as $node) {
            $arr[$node->getId()] = [self::WORKER => $this->getWorkers($node)];

            if (self::getFaucet($node)) {
                $arr[$node->getId()][self::FAUCET] = $this->getFaucet($node);
            }
        }

        return $arr;
    }

    /**
     * @return array
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
            self::WORKER_DEFAULT_PORT    => (int) $this->configs[self::WORKER_DEFAULT_PORT],
        ];
    }

    /**
     * @param Node $node
     *
     * @return array|null
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
     *
     * @return array
     * @throws TopologyConfigException
     */
    private function getWorkers(Node $node): array
    {
        switch ($node->getType()) {
            case TypeEnum::WEBHOOK:
            case TypeEnum::CRON:
            case TypeEnum::START:
                return [
                    self::TYPE => $this->getWorkerByType($node),
                ];
            case TypeEnum::BATCH:
            case TypeEnum::BATCH_CONNECTOR:
            case TypeEnum::RESEQUENCER:
            case TypeEnum::SPLITTER:
            case TypeEnum::DEBUG:
                return [
                    self::TYPE     => $this->getWorkerByType($node),
                    self::SETTINGS => [
                        self::PUBLISH_QUEUE => $this->getPublishQueue($node->getType()),
                    ],
                ];
            default:
                return [
                    self::TYPE     => $this->getWorkerByType($node),
                    self::SETTINGS => [
                        self::HOST          => $this->getHost($node->getType(), $node->getSystemConfigs()),
                        self::PROCESS_PATH  => $this->getPaths($node)[self::PROCESS_PATH],
                        self::STATUS_PATH   => $this->getPaths($node)[self::STATUS_PATH],
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
            case TypeEnum::BATCH:
                $workerType = self::SPLITTER_AMQRPC;
                break;
            case TypeEnum::BATCH_CONNECTOR:
                $workerType = self::SPLITTER_AMQRPC;
                break;
            case TypeEnum::WEBHOOK:
            case TypeEnum::GATEWAY:
            case TypeEnum::DEBUG:
            case TypeEnum::CRON:
            case TypeEnum::START:
                $workerType = self::WORKER_NULL;
                break;
            case TypeEnum::RESEQUENCER:
                $workerType = self::WORKER_RESEQUENCER;
                break;
            case TypeEnum::SPLITTER:
                $workerType = self::SPLITTER_JSON;
                break;
            case TypeEnum::XML_PARSER:
                $workerType = self::WORKER_HTTP_XML_PARSER;
                break;
            case TypeEnum::USER:
                $workerType = self::WORKER_LONG_RUNNING;
                break;
            default:
                $workerType = self::WORKER_HTTP;
        }

        return $workerType;
    }

    /**
     * @param Node $node
     *
     * @return array
     * @throws TopologyConfigException
     */
    public function getPaths(Node $node): array
    {
        switch ($node->getType()) {
            case TypeEnum::XML_PARSER:
                $paths = [
                    self::PROCESS_PATH => '/xml_parser',
                    self::STATUS_PATH  => '/xml_parser/test',
                ];
                break;
            case TypeEnum::TABLE_PARSER:
                $paths = [
                    self::PROCESS_PATH => sprintf('/parser/json/to/%s/', $node->getName()),
                    self::STATUS_PATH  => sprintf('/parser/json/to/%s/test', $node->getName()),
                ];
                break;
            case TypeEnum::FTP:
                $paths = [
                    self::PROCESS_PATH => '/connector/ftp/action',
                    self::STATUS_PATH  => '/connector/ftp/action/test',
                ];
                break;
            case TypeEnum::EMAIL:
                $paths = [
                    self::PROCESS_PATH => '/mailer/email',
                    self::STATUS_PATH  => '/mailer/email/test',
                ];
                break;
            case TypeEnum::MAPPER:
                $paths = [
                    self::PROCESS_PATH => sprintf('/mapper/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/mapper/%s/test', $node->getName()),
                ];
                break;
            case TypeEnum::CONNECTOR:
            case TypeEnum::BATCH_CONNECTOR:
                $paths = [
                    self::PROCESS_PATH => sprintf('/connector/%s/action', $node->getName()),
                    self::STATUS_PATH  => sprintf('/connector/%s/action/test', $node->getName()),
                ];
                break;
            case TypeEnum::CUSTOM:
                $paths = [
                    self::PROCESS_PATH => sprintf('/custom_node/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/custom_node/%s/process/test', $node->getName()),
                ];
                break;
            case TypeEnum::SIGNAL:
                $paths = [
                    self::PROCESS_PATH => '/custom_node/signal/process',
                    self::STATUS_PATH  => '/custom_node/signal/process/test',
                ];
                break;
            case TypeEnum::USER:
                $paths = [
                    self::PROCESS_PATH => sprintf('/longRunning/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/longRunning/%s/process/test', $node->getName()),
                ];
                break;
            case TypeEnum::API:
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
            case TypeEnum::XML_PARSER:
                $host = $this->configs[self::XML_PARSER_API_HOST];
                break;
            case  TypeEnum::FTP:
                $host = $this->configs[self::FTP_API_HOST];
                break;
            case TypeEnum::EMAIL:
                $host = $this->configs[self::MAILER_API_HOST];
                break;
            case TypeEnum::MAPPER:
                $host = $this->configs[self::MAPPER_API_HOST];
                break;
            case TypeEnum::BATCH_CONNECTOR:
            case TypeEnum::TABLE_PARSER:
            case TypeEnum::CONNECTOR:
            case TypeEnum::WEBHOOK:
            case TypeEnum::CUSTOM:
            case TypeEnum::SIGNAL:
            case TypeEnum::USER:
            case TypeEnum::API:
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
     * @return array|null
     */
    private function getPublishQueue(string $nodeType): ?array
    {
        switch ($nodeType) {
            case TypeEnum::BATCH:
            case TypeEnum::BATCH_CONNECTOR:
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
            case TypeEnum::API:
            case TypeEnum::BATCH:
            case TypeEnum::BATCH_CONNECTOR:
            case TypeEnum::CONNECTOR:
            case TypeEnum::CUSTOM:
            case TypeEnum::EMAIL:
            case TypeEnum::FTP:
            case TypeEnum::MAPPER:
            case TypeEnum::SIGNAL:
            case TypeEnum::TABLE_PARSER:
            case TypeEnum::USER:
            case TypeEnum::WEBHOOK:
                return 80;
            default:
                throw new TopologyConfigException(sprintf('Unknown type for port [%s].', $nodeType));

        }
    }

}
