<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;
use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\Utils\String\DsnParser;
use Hanaboso\Utils\String\Json;

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
    public const METRICS_DSN            = 'metrics_dsn';
    public const METRICS_SERVICE        = 'metrics_service';
    public const WORKER_DEFAULT_PORT    = 'worker_default_port';
    public const MONOLITH_API_HOST      = 'monolith_api_host';
    public const XML_PARSER_API_HOST    = 'xml_parser_api_host';
    public const MONGODB_DSN            = 'mongodb_dsn';
    public const UDP_LOGGER_URL         = 'udp_logger_url';
    public const TOPOLOGY_POD_LABELS    = 'topology_pod_labels';

    public const NODE_CONFIG = 'node_config';
    public const WORKER      = 'worker';
    public const TYPE        = 'type';
    public const SETTINGS    = 'settings';
    public const FAUCET      = 'faucet';
    public const PREFETCH    = 'prefetch';
    public const TIMEOUT     = 'timeout';
    public const APPLICATION = 'application';

    public const WORKER_NULL            = 'worker.null';
    public const WORKER_HTTP_XML_PARSER = 'worker.http_xml_parser';
    public const WORKER_USER            = 'worker.user';
    public const WORKER_HTTP            = 'worker.http';
    public const WORKER_BATCH           = 'worker.batch';
    public const HOST                   = 'host';

    public const PROCESS_PATH = 'process_path';
    public const STATUS_PATH  = 'status_path';
    public const METHOD       = 'method';
    public const PORT         = 'port';
    public const NAME         = 'name';
    public const HEADERS      = 'headers';

    /**
     * @var ObjectRepository<Sdk>&SdkRepository
     */
    private SdkRepository $sdkRepository;

    /**
     * TopologyConfigFactory constructor.
     *
     * @param mixed[]         $configs
     * @param DocumentManager $documentManager
     */
    public function __construct(private array $configs, DocumentManager $documentManager)
    {
        $parsed                              = DsnParser::rabbitParser($configs[self::RABBITMQ_DSN]);
        $this->configs[self::RABBITMQ_HOST]  = sprintf('%s:%s', $parsed[DsnParser::HOST], $parsed[DsnParser::PORT]);
        $this->configs[self::RABBITMQ_VHOST] = $parsed[DsnParser::VHOST] ?? '/';
        $this->configs[self::RABBITMQ_USER]  = $parsed[DsnParser::USER] ?? 'guest';
        $this->configs[self::RABBITMQ_PASS]  = $parsed[DsnParser::PASSWORD] ?? 'guest';
        $this->sdkRepository                 = $documentManager->getRepository(Sdk::class);
    }

    /**
     * @param mixed[] $nodes
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

        return Json::encode($result, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed[] $nodes
     *
     * @return mixed[]
     * @throws TopologyConfigException
     */
    private function loopNodes(array $nodes): array
    {
        $arr = [];
        /** @var Node $node */
        foreach ($nodes as $node) {
            $arr[$node->getId()] = $this->assembleNode($node);
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
            self::MONGODB_DSN            => $this->configs[self::MONGODB_DSN],
            self::METRICS_DSN            => $this->configs[self::METRICS_DSN],
            self::METRICS_SERVICE        => $this->configs[self::METRICS_SERVICE],
            self::WORKER_DEFAULT_PORT    => (int) $this->configs[self::WORKER_DEFAULT_PORT],
            self::UDP_LOGGER_URL         => $this->configs[self::UDP_LOGGER_URL],
            self::TOPOLOGY_POD_LABELS    => $this->configs[self::TOPOLOGY_POD_LABELS],
        ];
    }

    /**
     * @param Node $node
     *
     * @return mixed[][]|null
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
     * @return mixed[]|string[]
     * @throws TopologyConfigException
     * @throws TopologyException
     */
    private function getWorkers(Node $node): array
    {
        switch ($node->getType()) {
            case TypeEnum::WEBHOOK:
            case TypeEnum::CRON:
            case TypeEnum::START:
            case TypeEnum::USER:
                return [
                    self::TYPE => $this->getWorkerByType($node),
                ];
            case TypeEnum::DEBUG:
                return [
                    self::TYPE     => $this->getWorkerByType($node),
                    self::SETTINGS => [],
                ];
            default:
                $host   = $this->getHost($node->getType(), $node->getSystemConfigs());
                $path   = $this->getPaths($node);
                $parsed = explode(':', $host);

                return [
                    self::TYPE     => $this->getWorkerByType($node),
                    self::SETTINGS => [
                        self::HOST         => $parsed[0] ?? '',
                        self::PROCESS_PATH => $path[self::PROCESS_PATH],
                        self::STATUS_PATH  => $path[self::STATUS_PATH],
                        self::METHOD       => CurlManager::METHOD_POST,
                        self::PORT         => (int) ($parsed[1] ?? $this->getPort($node->getType())),
                        self::HEADERS      => $this->sdkRepository->findByHost($host),
                        self::APPLICATION  => $node->getApplication(),
                        self::PREFETCH     => $node->getSystemConfigs()?->getPrefetch(),
                        self::TIMEOUT      => $node->getSystemConfigs()?->getTimeout(),
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
        return match ($node->getType()) {
            TypeEnum::BATCH => self::WORKER_BATCH,
            TypeEnum::WEBHOOK, TypeEnum::GATEWAY, TypeEnum::DEBUG, TypeEnum::CRON, TypeEnum::START => self::WORKER_NULL,
            TypeEnum::XML_PARSER => self::WORKER_HTTP_XML_PARSER,
            TypeEnum::USER => self::WORKER_USER,
            default => self::WORKER_HTTP,
        };
    }

    /**
     * @param Node $node
     *
     * @return mixed[]|string[]
     * @throws TopologyConfigException
     */
    private function getPaths(Node $node): array
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
            case TypeEnum::CONNECTOR:
                $paths = [
                    self::PROCESS_PATH => sprintf('/connector/%s/action', $node->getName()),
                    self::STATUS_PATH  => sprintf('/connector/%s/action/test', $node->getName()),
                ];

                break;
            case TypeEnum::BATCH:
                $paths = [
                    self::PROCESS_PATH => sprintf('/batch/%s/action', $node->getName()),
                    self::STATUS_PATH  => sprintf('/batch/%s/action/test', $node->getName()),
                ];

                break;
            case TypeEnum::CUSTOM:
                $paths = [
                    self::PROCESS_PATH => sprintf('/custom-node/%s/process', $node->getName()),
                    self::STATUS_PATH  => sprintf('/custom-node/%s/process/test', $node->getName()),
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

        return match ($nodeType) {
            TypeEnum::XML_PARSER => $this->configs[self::XML_PARSER_API_HOST],
            TypeEnum::USER => '',
            TypeEnum::BATCH, TypeEnum::TABLE_PARSER, TypeEnum::CONNECTOR, TypeEnum::WEBHOOK, TypeEnum::CUSTOM => $this->configs[self::MONOLITH_API_HOST],
            default => throw new TopologyConfigException(sprintf('Unknown type of host [%s].', $nodeType)),
        };
    }

    /**
     * @param string $nodeType
     *
     * @return int
     * @throws TopologyConfigException
     */
    private function getPort(string $nodeType): int
    {
        return match ($nodeType) {
            TypeEnum::BATCH, TypeEnum::CONNECTOR, TypeEnum::CUSTOM, TypeEnum::TABLE_PARSER, TypeEnum::USER, TypeEnum::WEBHOOK => 80,
            default => throw new TopologyConfigException(sprintf('Unknown type for port [%s].', $nodeType)),
        };
    }

    /**
     * @param Node $node
     *
     * @return mixed[]
     * @throws TopologyConfigException
     */
    private function assembleNode(Node $node): array
    {
        $arr               = [];
        $arr[self::WORKER] = $this->getWorkers($node);

        if (self::getFaucet($node)) {
            $arr[self::FAUCET] = $this->getFaucet($node);
        }

        return $arr;
    }

}
