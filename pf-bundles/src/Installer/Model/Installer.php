<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Installer\Model;

use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Installer
 *
 * @package Hanaboso\PipesFramework\Installer\Model
 */
class Installer
{

    private const BASE_ROUTE                = 'dkr.hanaboso.net';
    private const PIPES_DEMO_MONOLITH_ROUTE = '/pipes/demo/monolith:';
    private const PIPES_PIPES_PFBRIDGE      = '/pipes/pipes/pf-bridge:';
    private const PIPES_NOTIFICATION_SENDER = '/pipes/notification-sender:';
    private const MASTER                    = 'master';

    public const KAPACITOR = 'kapacitor';

    public const ELASTICSEARCH = 'elasticsearch';
    public const RABBITMQ      = 'rabbitmq';
    public const REDIS         = 'redis';
    public const MONGO         = 'mongo';
    public const INFLUXDB      = 'influxdb';
    public const LOGSTASH      = 'logstash';

    /**
     * @var array
     */
    protected $logsServices = [
        'logs' => [
            self::ELASTICSEARCH,
            self::LOGSTASH,
        ],
    ];

    /**
     * @var array
     */
    protected $metricsServices = [
        'metrics' => [
            self::INFLUXDB,
            self::KAPACITOR,
            'telegraf',
        ],
    ];

    /**
     * @var array
     */
    protected $coreServices = [
        'core' => [
            'batch',
            'batch-connector',
            'long-running-node',
            'status-service',
            'multi-probe',
            'multi-counter',
            'starting-point',
            'repeater',
            'monolith-api',
            'notification-sender-api',
            'notification-sender-consumer',
            'topology-api',
            'frontend',
            'stream',
        ],
    ];

    /**
     * @var array
     */
    protected $databases = [
        'databases' => [
            self::MONGO,
            self::REDIS,
            self::RABBITMQ,
        ],
    ];

    /**
     * @var array
     */
    protected $logsVolumes = [
        'logs' => [
            self::ELASTICSEARCH,
        ],
    ];

    /**
     * @var array
     */
    protected $metricsVolumes = [
        'metrics' => [
            self::INFLUXDB,
        ],
    ];

    /**
     * @var array
     */
    protected $monolithEnvironments = [
        'BACKEND_HOST'         => '${BACKEND_URL}/',
        'ELASTIC_HOST'         => self::ELASTICSEARCH,
        'ELASTIC_INDEX'        => 'logstash-2018.01.31',
        'PHP_FPM_MAX_REQUESTS' => 5000,
        'SMTP_USER'            => 'root',
        'SMTP_PASSWORD'        => 'root',
        'METRICS_HOST'         => self::KAPACITOR,
        'METRICS_PORT'         => 9100,
        'METRICS_SERVICE'      => 'influx',
    ];

    /**
     * @var array
     */
    protected $pfBridgeEnvironments = [
        'RABBITMQ_HOST'    => self::RABBITMQ,
        'RABBITMQ_PORT'    => 5672,
        'RABBITMQ_USER'    => 'guest',
        'RABBITMQ_PASS'    => 'guest',
        'RABBITMQ_VHOST'   => '/',
        'REDIS_HOST'       => self::REDIS,
        'REDIS_PORT'       => 6379,
        'REDIS_PASS'       => '',
        'REDIS_DB'         => 0,
        'COUNTER_PREFETCH' => 100,
        'METRICS_HOST'     => self::KAPACITOR,
        'METRICS_PORT'     => 9100,
        'METRICS_SERVICE'  => 'influx',
    ];

    /**
     * @var array
     */
    protected $notificationEnvironments = [
        'RABBIT_HOST' => self::RABBITMQ,
        'RABBIT_PORT' => 5672,
        'RABBIT_USER' => 'guest',
        'RABBIT_PASS' => 'guest',
        'MONGO_HOST'  => self::MONGO,
    ];

    /**
     * @return array
     */
    private function getVersion(): array
    {

        return [
            'version' => '3.5',
        ];
    }

    /**
     * @return array
     */
    public function getNetwork(): array
    {

        return [
            'networks' => [
                'default' => [
                    'name' => 'pipes_default',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getBatchServices(): array
    {

        return [
            'batch' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_DEMO_MONOLITH_ROUTE, self::MASTER),
                'command'     => 'bin/console rabbit_mq:consumer:batch',
                'environment' => $this->monolithEnvironments,
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getBatchConnectorServices(): array
    {
        return [
            'batch-connector' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_DEMO_MONOLITH_ROUTE, self::MASTER),
                'command'     => 'bin/console rabbit_mq:consumer:batch-connector',
                'environment' => $this->monolithEnvironments,
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getLongRunningNodeServices(): array
    {
        return [
            'long-running-node' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_DEMO_MONOLITH_ROUTE, self::MASTER),
                'command'     => 'bin/console rabbit_mq:consumer:long-running-node',
                'environment' => $this->monolithEnvironments,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getStatusServiceServices(): array
    {
        return [
            'status-service' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_DEMO_MONOLITH_ROUTE, self::MASTER),
                'command'     => 'bin/console rabbit_mq:consumer:status-service',
                'environment' => $this->monolithEnvironments,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getMultiProbeServices(): array
    {
        return [
            'multi-probe' => [
                'image'       => sprintf('%s/pipes/pipes/multi-probe:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'REDIS_HOST' => self::REDIS,
                    'REDIS_PORT' => 6379,
                    'REDIS_PASS' => '',
                    'REDIS_DB'   => 0,

                ],

            ],
        ];

    }

    /**
     * @return array
     */
    protected function getMultiCounterServices(): array
    {

        return [
            'multi-counter' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_PIPES_PFBRIDGE, self::MASTER),
                'environment' => $this->pfBridgeEnvironments,
                'command'     => './dist/src/bin/pipes.js start multi_counter',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getStartingPointServices(): array
    {

        return [
            'starting-point' => [
                'image'       => sprintf('%s/pipes/pipes/starting-point:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'APP_DEBUG'                    => 'false',
                    'MONGO_HOSTNAME'               => self::MONGO,
                    'MONGO_DATABASE'               => 'demo',
                    'RABBIT_COUNTER_QUEUE_DURABLE' => 'true',
                    'RABBIT_QUEUE_DURABLE'         => 'true',
                    'RABBIT_DELIVERY_MODE'         => 2,
                    'APP_CLEANUP_TIME'             => 300,
                    'METRICS_HOST'                 => self::KAPACITOR,
                    'METRICS_PORT'                 => 9100,
                    'METRICS_SERVICE'              => self::INFLUXDB,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getRepeaterServices(): array
    {

        return [
            'repeater' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_PIPES_PFBRIDGE, self::MASTER),
                'command'     => './dist/src/bin/pipes.js start repeater',
                'environment' => $this->pfBridgeEnvironments,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getMonolithApiServices(): array
    {

        return [
            'monolith-api' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_DEMO_MONOLITH_ROUTE, self::MASTER),
                'environment' => $this->monolithEnvironments,
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getNotificationSenderApiServices(): array
    {

        return [
            'notification-sender-api' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_NOTIFICATION_SENDER, self::MASTER),
                'environment' => $this->notificationEnvironments,
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getTopologyApiServices(): array
    {

        return [
            'topology-api' => [
                'image'       => sprintf('%s/pipes/pipes/topology-api-v1:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'DEPLOYMENT_PREFIX'   => '${DEPLOYMENT_PREFIX}',
                    'GENERATOR_NETWORK'   => 'pipes_default',
                    'GENERATOR_MODE'      => 'compose',
                    'GENERATOR_PATH'      => '/tmp/topology',
                    'PROJECT_SOURCE_PATH' => '/tmp/topology',
                    'MONGO_HOST'          => self::MONGO,
                    'MONGO_DATABASE'      => 'demo',
                    'RABBITMQ_HOST'       => self::RABBITMQ,
                ],
                'volumes'     => [
                    '/var/run/docker-katerina.bellerova.sock:/var/run/docker.sock',
                    '/tmp/topology:/tmp/topology',
                ],
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getNotificationSenderConsumerServices(): array
    {
        return [
            'notification-sender-consumer' => [
                'image'       => sprintf('%s%s%s', self::BASE_ROUTE, self::PIPES_NOTIFICATION_SENDER, self::MASTER),
                'environment' => $this->notificationEnvironments,
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getFrontendServices(): array
    {

        return [
            'frontend' => [
                'image'       => sprintf('%s/pipes/pipes/frontend:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'BACKEND_URL'  => '${BACKEND_URL}',
                    'FRONTEND_URL' => '${BACKEND_URL}',
                    'PHP_WEBROOT'  => '/var/www/html/public',
                ],
                'ports'       => [
                    '${PUBLISH_HTTP_PORT}:80',
                ],
            ],
        ];

    }

    /**
     * @return array
     */
    protected function getStreamServices(): array
    {

        return [
            'stream' => [
                'image'       => sprintf('%s/pipes/pipes/stream:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'RABBITMQ_HOST'    => self::RABBITMQ,
                    'STREAM_WS_PORT'   => 80,
                    'STREAM_HTTP_PORT' => 3030,
                    'STREAM_QUEUE'     => 'pipes.stream',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getRabbitmqServices(): array
    {

        return [
            self::RABBITMQ => [
                'image'   => sprintf('%s:management-alpine', self::RABBITMQ),
                'volumes' => [sprintf('%s:/var/lib/%s', self::RABBITMQ, self::RABBITMQ)],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getMongoServices(): array
    {

        return [
            self::MONGO => [
                'image'   => sprintf('%s:latest', self::MONGO),
                'volumes' => [sprintf('%s:/data/db', self::MONGO)],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getRedisServices(): array
    {

        return [
            self::REDIS => [
                'image'   => sprintf('%s:alpine', self::REDIS),
                'volumes' => [sprintf('%s:/data', self::REDIS)],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLogstashServices(): array
    {

        return [
            self::LOGSTASH => [
                'image'       => sprintf('%s/pipes/pipes/logstash:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'MONGO_HOST'       => self::MONGO,
                    'MONGO_DATABASE'   => 'demo',
                    'MONGO_COLLECTION' => 'Logs',
                    'LS_JAVA_OPTS'     => '-Xms512m -Xmx512m',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getInfluxdbServices(): array
    {

        return [
            self::INFLUXDB => [
                'image'       => sprintf('%s/pipes/pipes/influxdb:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'INFLUXDB_DB' => 'pipes',
                ],
                'volumes'     => [sprintf('%s:/var/lib/influxdb', self::INFLUXDB)],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getTelegrafServices(): array
    {

        return [
            'telegraf' => [
                'image'       => sprintf('%s/pipes/pipes/rabbitmq-telegraf:%s', self::BASE_ROUTE, self::MASTER),
                'environment' => [
                    'METRICS_SERVICE' => 'influx',
                    'METRICS_HOST'    => self::KAPACITOR,
                    'METRICS_PORT'    => 9092,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getKapacitorServices(): array
    {

        return [
            self::KAPACITOR => [
                'image'    => sprintf('%s/pipes/pipes/kapacitor:%s', self::BASE_ROUTE, self::MASTER),
                'hostname' => self::KAPACITOR,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getCronApiServices(): array
    {

        return [
            'cron-api' => [
                'image' => sprintf('%s/pipes/pipes/python-cron:%s', self::BASE_ROUTE, self::MASTER),
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getElasticsearchServices(): array
    {

        return [
            self::ELASTICSEARCH =>
                [
                    'image'       => 'docker.elastic.co/elasticsearch/elasticsearch-oss:7.3.1',
                    'volumes'     => [sprintf('%s:/usr/share/elasticsearch/data', self::ELASTICSEARCH)],
                    'environment' => [
                        'cluster.initial_master_nodes' => '-1',
                    ],
                ],
        ];
    }

    /**
     * @return array
     */
    protected function getInfluxdbVolumes(): array
    {

        return [
            self::INFLUXDB => [
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getMongoVolumes(): array
    {

        return [
            self::MONGO => [
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getElasticsearchVolumes(): array
    {

        return [
            self::ELASTICSEARCH => [
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getRabbitmqVolumes(): array
    {

        return [
            self::RABBITMQ => [
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getRedisVolumes(): array
    {

        return [
            self::REDIS => [

            ],
        ];
    }

    /**
     * @return array
     */
    protected function getAllVolumes(): array
    {

        return $this->logsVolumes + $this->metricsVolumes + $this->databasesVolumes;

    }

    /**
     * @param string $str
     * @param string $delim
     *
     * @return string
     */
    public function convertToCamel(string $str, string $delim): string
    {
        $explodedStr      = explode($delim, $str);
        $explodedStrCamel = array_map('ucwords', (array) $explodedStr);

        return implode('', $explodedStrCamel);
    }

    /**
     * @param string $value
     * @param array  $array
     *
     * @return array
     * @throws Exception
     */
    public function unsetValue(string $value, array $array): array
    {
        $key = array_search($value, $array);
        if ($key !== FALSE) {
            unset($array[$key]);

        } else {
            throw new Exception('Value is wrong and couldnt be unset.');
        }

        return $array;
    }

    /**
     * @param string        $component
     * @param DataTransport $dto
     *
     * @return array
     * @var DataTransport   $dto
     */
    private function createBaseComponent(string $component, DataTransport $dto): array
    {

        $array       = [];
        $logsName    = sprintf('logs%s', ucfirst($component));
        $metricsName = sprintf('metrics%s', ucfirst($component));

        $key = array_search($dto->getLog(), $this->$logsName['logs']);

        if (is_int($key)) {
            $array = array_merge($array, [$this->$logsName['logs'][$key]]);
        }

        if ($dto->getMetric() === self::INFLUXDB) {

            $array = array_merge($array, $this->$metricsName['metrics']);
        } elseif ($dto->getMetric() === self::MONGO) {

            $keys = [self::INFLUXDB, self::KAPACITOR];
            foreach ($keys as $key) {
                $index = array_search($key, $this->$metricsName['metrics']);
                if ($index !== FALSE) {
                    unset($this->$metricsName['metrics'][$index]);
                }
            }

            $this->$metricsName['metrics'] = $this->unsetMetrics($this->$metricsName['metrics']);

            $array = array_merge($array, $this->$metricsName['metrics']);

        }

        if ($component === 'services') {
            $array = array_merge($array, $this->coreServices['core']);
        }

        if ($dto->getDatabase()) {

            $array = array_merge($array, $this->databases['databases']);

        }

        return $array;

    }

    /**
     * @param array $item
     *
     * @return array
     */
    private function resetComponent(array $item): array
    {
        $name = array_keys($item)[0];

        if (isset($item[$name]['environment']['METRICS_HOST'])) {
            $item[$name]['environment']['METRICS_HOST'] = self::MONGO;
        }
        if (isset($item[$name]['environment']['METRICS_PORT'])) {
            $item[$name]['environment']['METRICS_PORT'] = 27017;
        }
        if (isset($item[$name]['environment']['METRICS_SERVICE'])) {
            $item[$name]['environment']['METRICS_SERVICE'] = self::MONGO;
        }

        return $item;
    }

    /**
     * @param array $metricsName
     *
     * @return array
     */
    private function unsetMetrics(array $metricsName): array
    {

        $keys = [self::INFLUXDB, self::KAPACITOR];
        foreach ($keys as $key) {
            $index = array_search($key, $metricsName);
            if ($index !== FALSE) {
                unset($metricsName[$index]);
            }
        }

        return $metricsName;
    }

    /**
     * @param string        $component
     * @param DataTransport $dto
     *
     * @return array
     */
    private function getComponent(string $component, DataTransport $dto): array
    {

        $list             = [];
        $list[$component] = [];

        foreach ($this->createBaseComponent($component, $dto) as $item) {

            $methodName = sprintf('get%s%s', $this->convertToCamel($item, '-'), ucfirst($component));
            $item       = $this->$methodName();
            if ($dto->getMetric() === self::MONGO) {
                $item = $this->resetComponent($this->$methodName());
            }
            $list[$component] = array_merge($list[$component], $item);
        }

        return $list;
    }

    /**
     * @param DataTransport $dto
     *
     * @return array
     */
    public function createArray(DataTransport $dto): array
    {

        $installer = [];

        $installer[0] = array_merge(
            $this->getVersion(),
            $this->getComponent('services', $dto),
            $this->getComponent('volumes', $dto),
            $this->getNetwork()
        );

        $installer[1] =
            'BACKEND_URL=test
            DEPLOYMENT_PREFIX=compose
            TAG_MONOLITH=dev
            PUBLISH_HTTP_PORT=80';

        return $installer;

    }

    /**
     * @param DataTransport $dto
     *
     * @return string
     */
    public function createInstaller(DataTransport $dto): string
    {
        $installer = $this->createArray($dto);

        return Yaml::dump($installer[0], 8, 8);

    }

    /**
     * @return array
     */
    protected function getVolumes3(): array
    {
        return [
            'volumes' => [
                self::INFLUXDB => [
                    'driver'      => 'local',
                    'driver_opts' => [
                        'type'   => 'none',
                        'device' => '/srv/persistent-data/${DEPLOYMENT_PREFIX}/influxdb',
                        'o'      => 'bind',

                    ],
                ],
                self::MONGO    => [
                    'driver'      => 'local',
                    'driver_opts' => [
                        'type'   => 'none',
                        'device' => '/srv/persistent-data/${DEPLOYMENT_PREFIX}/mongodb',
                        'o'      => 'bind',

                    ],
                ],
                self::RABBITMQ => [
                    'driver'      => 'local',
                    'driver_opts' => [
                        'type'   => 'none',
                        'device' => sprintf('/srv/persistent-data/${DEPLOYMENT_PREFIX}/%s', self::RABBITMQ),
                        'o'      => 'bind',

                    ],
                ],
                self::REDIS    => [
                    'driver'      => 'local',
                    'driver_opts' => [
                        'type'   => 'none',
                        'device' => sprintf('/srv/persistent-data/${DEPLOYMENT_PREFIX}/%s', self::REDIS),
                        'o'      => 'bind',

                    ],
                ],
            ],
        ];

    }

}
