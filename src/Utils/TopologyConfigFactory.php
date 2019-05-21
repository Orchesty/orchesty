<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Class TopologyConfigFactory
 *
 * @package Hanaboso\PipesFramework\Utils
 */
final class TopologyConfigFactory
{

    public const ENVIRONMENT            = 'enviroment';
    public const DOCKER_REGISTRY        = 'docker_registry';
    public const DOCKER_PF_BRIDGE_IMAGE = 'docker_pf_bridge_image';
    public const RABBITMQ_HOST          = 'rabbitmq_host';
    public const RABBITMQ_USER          = 'rabbitmq_user';
    public const RABBITMQ_PASS          = 'rabbitmq_pass';
    public const RABBITMQ_VHOST         = 'rabbitmq_vhost';
    public const MULTI_PROBE_HOST       = 'multi_probe_host';
    public const METRICS_HOST           = 'metrics_host';
    public const WORKER_DEFAULT_PORT    = 'worker_default_port';

    public const NODE_CONFIG     = 'node_config';
    public const WORKER          = 'worker';
    public const TYPE            = 'type';
    public const SETTINGS        = 'settings';
    public const FAUCET          = 'faucet';
    public const PREFETCH        = 'prefetch';
    public const SPLITTER_AMQRPC = 'splitter.amqrpc';

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
     * @param array $nodes
     *
     * @return string
     * @throws Exception
     */
    public static function create(array $nodes): string
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../../.env');

        $result = [
            self::ENVIRONMENT => self::getEnvParameters(),
            self::NODE_CONFIG => self::loopNodes($nodes),
        ];

        return (string) json_encode($result);
    }

    /**
     * @param array $nodes
     *
     * @return array
     * @throws Exception
     */
    private static function loopNodes(array $nodes): array
    {
        $arr = [];
        /** @var Node $node */
        foreach ($nodes as $node) {
            $arr[$node->getId()] = [
                self::FAUCET => self::getFaucet($node),
                self::WORKER => self::getWorkers($node),
            ];
        }

        return $arr;
    }

    /**
     * @return array
     */
    private static function getEnvParameters(): array
    {
        return [
            self::ENVIRONMENT => [
                self::DOCKER_REGISTRY        => $_ENV[self::DOCKER_REGISTRY],
                self::DOCKER_PF_BRIDGE_IMAGE => $_ENV[self::DOCKER_PF_BRIDGE_IMAGE],
                self::RABBITMQ_HOST          => $_ENV[self::RABBITMQ_HOST],
                self::RABBITMQ_USER          => $_ENV['RABBITMQ_DEFAULT_USER'],
                self::RABBITMQ_PASS          => $_ENV['RABBITMQ_DEFAULT_PASS'],
                self::RABBITMQ_VHOST         => $_ENV['RABBITMQ_DEFAULT_VHOST'],
                self::MULTI_PROBE_HOST       => $_ENV[self::MULTI_PROBE_HOST],
                self::METRICS_HOST           => $_ENV[self::METRICS_HOST],
                self::WORKER_DEFAULT_PORT    => $_ENV[self::WORKER_DEFAULT_PORT],
            ],
        ];
    }

    /**
     * @param Node $node
     *
     * @return array|null
     */
    private static function getFaucet(Node $node): ?array
    {
        if ($node->getSystemConfigs()) {
            return [
                self::SETTINGS => [
                    self::PREFETCH =>
                        json_decode((string) $node->getSystemConfigs(), TRUE, 512, JSON_THROW_ON_ERROR),
                ],
            ];
        } else {
            return NULL;
        }
    }

    /**
     * @param Node $node
     *
     * @return array
     * @throws Exception
     */
    private static function getWorkers(Node $node): array
    {
        return [
            self::TYPE     => self::getWorkerByType($node),
            self::SETTINGS => [
                self::HOST          => self::getHost($node->getType()),
                self::PROCESS_PATH  => self::getPaths($node->getType())[self::PROCESS_PATH],
                self::STATUS_PATH   => self::getPaths($node->getType())[self::STATUS_PATH],
                self::METHOD        => 'POST',
                self::PORT          => self::getPort($node->getType()),
                self::PUBLISH_QUEUE => self::getPublishQueue($node->getType()),
            ],
        ];
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    private static function getWorkerByType(Node $node): string
    {
        switch ($node->getType()) {
            case TypeEnum::BATCH:
                $workerType = self::SPLITTER_AMQRPC;
                break;
            case TypeEnum::BATCH_CONNECTOR:
                $workerType = self::SPLITTER_AMQRPC;
                break;
            case TypeEnum::CRON:
                $workerType = self::WORKER_NULL;
                break;
            case TypeEnum::DEBUG:
                $workerType = self::WORKER_NULL;
                break;
            case TypeEnum::GATEWAY:
                $workerType = self::WORKER_NULL;
                break;
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
     * @param string $nodeType
     *
     * @return array
     * @throws Exception
     */
    public static function getPaths(string $nodeType): array
    {
        switch ($nodeType) {
            case TypeEnum::XML_PARSER:
                $paths = [
                    self::PROCESS_PATH => '/xml_parser',
                    self::STATUS_PATH  => '/xml_parser/test',
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
                    self::PROCESS_PATH => '/mapper/mapper',
                    self::STATUS_PATH  => '/mapper/mapper/test',
                ];
                break;
            case TypeEnum::CONNECTOR:
                $paths = [
                    self::PROCESS_PATH => '/connector/connector/action',
                    self::STATUS_PATH  => '/connector/connector/action/test',
                ];
                break;
            case TypeEnum::WEBHOOK:
                $paths = [
                    self::PROCESS_PATH => '/connector/webhook/webhook',
                    self::STATUS_PATH  => '/connector/webhook/webhook/test',
                ];
                break;
            case TypeEnum::CUSTOM:
                $paths = [
                    self::PROCESS_PATH => '/custom_node/custom/process',
                    self::STATUS_PATH  => '/custom_node/custom/process/test',
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
                    self::PROCESS_PATH => '/longRunning/user/process',
                    self::STATUS_PATH  => '/longRunning/user/process/test',
                ];
                break;
            case TypeEnum::API:
                $paths = [
                    self::PROCESS_PATH => '/connector/api/action',
                    self::STATUS_PATH  => '/connector/api/action/test',
                ];
                break;
            default:
                throw new Exception(sprintf('Unknown type of routing.'));
        }

        return $paths;
    }

    /**
     * @param string $nodeType
     *
     * @return string
     * @throws Exception
     */
    private static function getHost(string $nodeType): string
    {

        switch ($nodeType) {
            case TypeEnum::XML_PARSER:
                $host = 'xml-parser-api';
                break;
            case  TypeEnum::FTP:
                $host = 'ftp-api';
                break;
            case TypeEnum::EMAIL:
                $host = 'mailer-api';
                break;
            case TypeEnum::MAPPER:
                $host = 'mapper-api';
                break;
            case TypeEnum::CONNECTOR or TypeEnum::WEBHOOK or TypeEnum::CUSTOM or TypeEnum::SIGNAL or TypeEnum::USER or TypeEnum::API:
                $host = 'monolith-api';
                break;
            default:
                throw new Exception(sprintf('Unknow type of host'));
        }

        return $host;

    }

    /**
     * @param string $nodeType
     *
     * @return array
     */
    private static function getPublishQueue(string $nodeType): array
    {
        if ($nodeType === TypeEnum::BATCH or $nodeType === TypeEnum::BATCH_CONNECTOR) {
            return [
                self::NAME => sprintf('pipes.%s', $nodeType),
            ];
        } else {
            return [];
        }
    }

    /**
     * @param string $nodeType
     *
     * @return int
     * @throws Exception
     */
    private static function getPort(string $nodeType): int
    {
        if ($nodeType === TypeEnum::XML_PARSER
            or $nodeType === TypeEnum::FTP
            or $nodeType === TypeEnum::EMAIL
            or $nodeType === TypeEnum::MAPPER
            or $nodeType === TypeEnum::API
            or $nodeType === TypeEnum::CONNECTOR
            or $nodeType === TypeEnum::WEBHOOK
            or $nodeType === TypeEnum::CUSTOM
            or $nodeType === TypeEnum::SIGNAL
            or $nodeType === TypeEnum::USER
        ) {
            return 80;
        } else {
            throw new Exception('Unknown type for port');
        }
    }

}