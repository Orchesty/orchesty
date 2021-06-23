<?php declare(strict_types=1);

namespace PortalTests\Controller;

use Hanaboso\Portal\Model\Installer\Installer;
use PortalTests\ControllerTestCaseAbstract;

/**
 * Class InstallerControllerTest
 *
 * @package PortalTests\Controller
 */
final class InstallerControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\Portal\Controller\InstallerController
     * @covers \Hanaboso\Portal\Controller\InstallerController::installerAction
     * @covers \Hanaboso\Portal\Handler\InstallerHandler
     * @covers \Hanaboso\Portal\Handler\InstallerHandler::getInstaller
     * @covers \Hanaboso\Portal\Model\Installer\Installer
     * @covers \Hanaboso\Portal\Model\Installer\Installer::generate
     * @covers \Hanaboso\Portal\Model\Installer\Installer::createArray
     * @covers \Hanaboso\Portal\Model\Installer\Installer::getVersion
     * @covers \Hanaboso\Portal\Model\Installer\Installer::getComponent
     * @covers \Hanaboso\Portal\Model\Installer\Installer::getNetwork
     * @covers \Hanaboso\Portal\Model\Installer\Installer::createBaseComponent
     * @covers \Hanaboso\Portal\Model\Installer\Installer::convertToCamel
     * @covers \Hanaboso\Portal\Model\Installer\Installer::resetComponent
     * @covers \Hanaboso\Portal\Model\Installer\DataTransport
     */
    public function testInstaller(): void
    {
        $response1 = $this->sendPost(
            '/installer',
            [
                'logs'     => Installer::LOGSTASH,
                'metrics'  => Installer::INFLUXDB,
                'database' => FALSE,
            ],
        );

        $response2 = $this->sendPost(
            '/installer',
            [
                'logs'     => Installer::ELASTICSEARCH,
                'metrics'  => Installer::MONGO,
                'database' => TRUE,
            ],
        );

        self::assertEquals(200, $response1->getStatus());
        self::assertEquals($this->getResponseLogstashInfluxb(), $response1->getContent()[0]);

        self::assertEquals(200, $response2->getStatus());
        self::assertEquals($this->getResponseElasticsearchMongo(), $response2->getContent()[0]);

        $response3 = $this->sendPost(
            '/installer',
            [
                'logs'     => 'xx',
                'metrics'  => 'yy',
                'database' => TRUE,
            ],
        );

        self::assertEquals(500, $response3->getStatus());
        self::assertEquals('Insert correct value to log', $response3->getContent()['message']);
    }

    /**
     * @return string
     */
    public function getResponseElasticsearchMongo(): string
    {
        return 'version: \'3.5\'
services:
        elasticsearch:
                image: \'docker.elastic.co/elasticsearch/elasticsearch-oss:7.3.1\'
                volumes:
                        - \'elasticsearch:/usr/share/elasticsearch/data\'
                environment:
                        cluster.initial_master_nodes: \'-1\'
        telegraf:
                image: \'dkr.hanaboso.net/pipes/pipes/rabbitmq-telegraf:master\'
                environment:
                        METRICS_SERVICE: mongo
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
        batch:
                image: \'dkr.hanaboso.net/pipes/demo/monolith:master\'
                command: \'bin/console rabbit_mq:consumer:batch\'
                environment:
                        BACKEND_HOST: \'${BACKEND_URL}/\'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        batch-connector:
                image: \'dkr.hanaboso.net/pipes/demo/monolith:master\'
                command: \'bin/console rabbit_mq:consumer:batch-connector\'
                environment:
                        BACKEND_HOST: \'${BACKEND_URL}/\'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        long-running-node:
                image: \'dkr.hanaboso.net/pipes/demo/monolith:master\'
                command: \'bin/console rabbit_mq:consumer:long-running-node\'
                environment:
                        BACKEND_HOST: \'${BACKEND_URL}/\'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        status-service:
                image: \'dkr.hanaboso.net/pipes/demo/monolith:master\'
                command: \'bin/console rabbit_mq:consumer:status-service\'
                environment:
                        BACKEND_HOST: \'${BACKEND_URL}/\'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        multi-probe:
                image: \'dkr.hanaboso.net/pipes/pipes/multi-probe:master\'
                environment:
                        REDIS_HOST: redis
                        REDIS_PORT: 6379
                        REDIS_PASS: \'\'
                        REDIS_DB: 0
        multi-counter:
                image: \'dkr.hanaboso.net/pipes/pipes/pf-bridge:master\'
                environment:
                        RABBITMQ_HOST: rabbitmq
                        RABBITMQ_PORT: 5672
                        RABBITMQ_USER: guest
                        RABBITMQ_PASS: guest
                        RABBITMQ_VHOST: /
                        REDIS_HOST: redis
                        REDIS_PORT: 6379
                        REDIS_PASS: \'\'
                        REDIS_DB: 0
                        COUNTER_PREFETCH: 100
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
                command: \'./dist/src/bin/pipes.js start multi_counter\'
        starting-point:
                image: \'dkr.hanaboso.net/pipes/pipes/starting-point:master\'
                environment:
                        APP_DEBUG: \'false\'
                        MONGO_HOSTNAME: mongo
                        MONGO_DATABASE: demo
                        RABBIT_COUNTER_QUEUE_DURABLE: \'true\'
                        RABBIT_QUEUE_DURABLE: \'true\'
                        RABBIT_DELIVERY_MODE: 2
                        APP_CLEANUP_TIME: 300
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        repeater:
                image: \'dkr.hanaboso.net/pipes/pipes/pf-bridge:master\'
                command: \'./dist/src/bin/pipes.js start repeater\'
                environment:
                        RABBITMQ_HOST: rabbitmq
                        RABBITMQ_PORT: 5672
                        RABBITMQ_USER: guest
                        RABBITMQ_PASS: guest
                        RABBITMQ_VHOST: /
                        REDIS_HOST: redis
                        REDIS_PORT: 6379
                        REDIS_PASS: \'\'
                        REDIS_DB: 0
                        COUNTER_PREFETCH: 100
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        monolith-api:
                image: \'dkr.hanaboso.net/pipes/demo/monolith:master\'
                environment:
                        BACKEND_HOST: \'${BACKEND_URL}/\'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: mongo
                        METRICS_PORT: 27017
                        METRICS_SERVICE: mongo
        notification-sender-api:
                image: \'dkr.hanaboso.net/pipes/pipes/notification-sender:master\'
                environment:
                        RABBIT_HOST: rabbitmq
                        RABBIT_PORT: 5672
                        RABBIT_USER: guest
                        RABBIT_PASS: guest
                        MONGO_HOST: mongo
        notification-sender-consumer:
                image: \'dkr.hanaboso.net/pipes/pipes/notification-sender:master\'
                environment:
                        RABBIT_HOST: rabbitmq
                        RABBIT_PORT: 5672
                        RABBIT_USER: guest
                        RABBIT_PASS: guest
                        MONGO_HOST: mongo
        topology-api:
                image: \'dkr.hanaboso.net/pipes/pipes/topology-api-v1:master\'
                environment:
                        DEPLOYMENT_PREFIX: \'${DEPLOYMENT_PREFIX}\'
                        GENERATOR_NETWORK: pipes_default
                        GENERATOR_MODE: compose
                        GENERATOR_PATH: /tmp/topology
                        PROJECT_SOURCE_PATH: /tmp/topology
                        MONGO_HOST: mongo
                        MONGO_DATABASE: demo
                        RABBITMQ_HOST: rabbitmq
                volumes:
                        - \'/var/run/docker-katerina.bellerova.sock:/var/run/docker.sock\'
                        - \'/tmp/topology:/tmp/topology\'
        frontend:
                image: \'dkr.hanaboso.net/pipes/pipes/frontend:master\'
                environment:
                        BACKEND_URL: \'${BACKEND_URL}\'
                        FRONTEND_URL: \'${BACKEND_URL}\'
                        PHP_WEBROOT: /var/www/html/public
                ports:
                        - \'${PUBLISH_HTTP_PORT}:80\'
        stream:
                image: \'dkr.hanaboso.net/pipes/pipes/stream:master\'
                environment:
                        RABBITMQ_HOST: rabbitmq
                        STREAM_WS_PORT: 80
                        STREAM_HTTP_PORT: 3030
                        STREAM_QUEUE: pipes.stream
        mongo:
                image: \'mongo:latest\'
                volumes:
                        - \'mongo:/data/db\'
        redis:
                image: \'redis:alpine\'
                volumes:
                        - \'redis:/data\'
        rabbitmq:
                image: \'rabbitmq:management-alpine\'
                volumes:
                        - \'rabbitmq:/var/lib/rabbitmq\'
volumes:
        elasticsearch: {  }
        mongo: {  }
        redis: {  }
        rabbitmq: {  }
networks:
        default:
                name: pipes_default
';
    }

    /**
     * @return string
     */
    public function getResponseLogstashInfluxb(): string
    {
        return "version: '3.5'
services:
        logstash:
                image: 'dkr.hanaboso.net/pipes/pipes/logstash:master'
                environment:
                        MONGO_HOST: mongo
                        MONGO_DATABASE: demo
                        MONGO_COLLECTION: Logs
                        LS_JAVA_OPTS: '-Xms512m -Xmx512m'
        influxdb:
                image: 'dkr.hanaboso.net/pipes/pipes/influxdb:master'
                environment:
                        INFLUXDB_DB: pipes
                volumes:
                        - 'influxdb:/var/lib/influxdb'
        kapacitor:
                image: 'dkr.hanaboso.net/pipes/pipes/kapacitor:master'
                hostname: kapacitor
        telegraf:
                image: 'dkr.hanaboso.net/pipes/pipes/rabbitmq-telegraf:master'
                environment:
                        METRICS_SERVICE: influx
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9092
        batch:
                image: 'dkr.hanaboso.net/pipes/demo/monolith:master'
                command: 'bin/console rabbit_mq:consumer:batch'
                environment:
                        BACKEND_HOST: '\${BACKEND_URL}/'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
        batch-connector:
                image: 'dkr.hanaboso.net/pipes/demo/monolith:master'
                command: 'bin/console rabbit_mq:consumer:batch-connector'
                environment:
                        BACKEND_HOST: '\${BACKEND_URL}/'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
        long-running-node:
                image: 'dkr.hanaboso.net/pipes/demo/monolith:master'
                command: 'bin/console rabbit_mq:consumer:long-running-node'
                environment:
                        BACKEND_HOST: '\${BACKEND_URL}/'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
        status-service:
                image: 'dkr.hanaboso.net/pipes/demo/monolith:master'
                command: 'bin/console rabbit_mq:consumer:status-service'
                environment:
                        BACKEND_HOST: '\${BACKEND_URL}/'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
        multi-probe:
                image: 'dkr.hanaboso.net/pipes/pipes/multi-probe:master'
                environment:
                        REDIS_HOST: redis
                        REDIS_PORT: 6379
                        REDIS_PASS: ''
                        REDIS_DB: 0
        multi-counter:
                image: 'dkr.hanaboso.net/pipes/pipes/pf-bridge:master'
                environment:
                        RABBITMQ_HOST: rabbitmq
                        RABBITMQ_PORT: 5672
                        RABBITMQ_USER: guest
                        RABBITMQ_PASS: guest
                        RABBITMQ_VHOST: /
                        REDIS_HOST: redis
                        REDIS_PORT: 6379
                        REDIS_PASS: ''
                        REDIS_DB: 0
                        COUNTER_PREFETCH: 100
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
                command: './dist/src/bin/pipes.js start multi_counter'
        starting-point:
                image: 'dkr.hanaboso.net/pipes/pipes/starting-point:master'
                environment:
                        APP_DEBUG: 'false'
                        MONGO_HOSTNAME: mongo
                        MONGO_DATABASE: demo
                        RABBIT_COUNTER_QUEUE_DURABLE: 'true'
                        RABBIT_QUEUE_DURABLE: 'true'
                        RABBIT_DELIVERY_MODE: 2
                        APP_CLEANUP_TIME: 300
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influxdb
        repeater:
                image: 'dkr.hanaboso.net/pipes/pipes/pf-bridge:master'
                command: './dist/src/bin/pipes.js start repeater'
                environment:
                        RABBITMQ_HOST: rabbitmq
                        RABBITMQ_PORT: 5672
                        RABBITMQ_USER: guest
                        RABBITMQ_PASS: guest
                        RABBITMQ_VHOST: /
                        REDIS_HOST: redis
                        REDIS_PORT: 6379
                        REDIS_PASS: ''
                        REDIS_DB: 0
                        COUNTER_PREFETCH: 100
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
        monolith-api:
                image: 'dkr.hanaboso.net/pipes/demo/monolith:master'
                environment:
                        BACKEND_HOST: '\${BACKEND_URL}/'
                        ELASTIC_HOST: elasticsearch
                        ELASTIC_INDEX: logstash-2018.01.31
                        PHP_FPM_MAX_REQUESTS: 5000
                        SMTP_USER: root
                        SMTP_PASSWORD: root
                        METRICS_HOST: kapacitor
                        METRICS_PORT: 9100
                        METRICS_SERVICE: influx
        notification-sender-api:
                image: 'dkr.hanaboso.net/pipes/pipes/notification-sender:master'
                environment:
                        RABBIT_HOST: rabbitmq
                        RABBIT_PORT: 5672
                        RABBIT_USER: guest
                        RABBIT_PASS: guest
                        MONGO_HOST: mongo
        notification-sender-consumer:
                image: 'dkr.hanaboso.net/pipes/pipes/notification-sender:master'
                environment:
                        RABBIT_HOST: rabbitmq
                        RABBIT_PORT: 5672
                        RABBIT_USER: guest
                        RABBIT_PASS: guest
                        MONGO_HOST: mongo
        topology-api:
                image: 'dkr.hanaboso.net/pipes/pipes/topology-api-v1:master'
                environment:
                        DEPLOYMENT_PREFIX: '\${DEPLOYMENT_PREFIX}'
                        GENERATOR_NETWORK: pipes_default
                        GENERATOR_MODE: compose
                        GENERATOR_PATH: /tmp/topology
                        PROJECT_SOURCE_PATH: /tmp/topology
                        MONGO_HOST: mongo
                        MONGO_DATABASE: demo
                        RABBITMQ_HOST: rabbitmq
                volumes:
                        - '/var/run/docker-katerina.bellerova.sock:/var/run/docker.sock'
                        - '/tmp/topology:/tmp/topology'
        frontend:
                image: 'dkr.hanaboso.net/pipes/pipes/frontend:master'
                environment:
                        BACKEND_URL: '\${BACKEND_URL}'
                        FRONTEND_URL: '\${BACKEND_URL}'
                        PHP_WEBROOT: /var/www/html/public
                ports:
                        - '\${PUBLISH_HTTP_PORT}:80'
        stream:
                image: 'dkr.hanaboso.net/pipes/pipes/stream:master'
                environment:
                        RABBITMQ_HOST: rabbitmq
                        STREAM_WS_PORT: 80
                        STREAM_HTTP_PORT: 3030
                        STREAM_QUEUE: pipes.stream
volumes:
        influxdb: {  }
networks:
        default:
                name: pipes_default
";
    }

}
