import {AssertionPublisher} from "amqplib-plus/dist/lib/AssertPublisher";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Container} from "hb-utils/dist/lib/Container";
import {Metrics} from "metrics-sender/dist/lib/metrics/Metrics";
import {
    amqpConnectionOptions, limiterOptions, metricsOptions, multiProbeOptions, persistentMode, redisStorageOptions,
    topologyTerminatorOptions,
} from "./config";
import RedisStorage from "./counter/storage/RedisStorage";
import LimiterPublisher from "./limiter/amqp/LimiterPublisher";
import {default as Limiter} from "./limiter/Limiter";
import TcpClient from "./limiter/TcpClient";
import logger from "./logger/Logger";
import CounterPublisher, {ICounterPublisherSettings} from "./node/drain/amqp/CounterPublisher";
import FollowersPublisher from "./node/drain/amqp/FollowersPublisher";
import {default as AmqpDrain, IAmqpDrainSettings} from "./node/drain/AmqpDrain";
import IPartialForwarder from "./node/drain/IPartialForwarder";
import {default as AmqpFaucet, IAmqpFaucetSettings} from "./node/faucet/AmqpFaucet";
import {IAmqpWorkerSettings} from "./node/worker/AAmqpWorker";
import AmqpNonBlockingWorker from "./node/worker/AmqpNonBlockingWorker";
import AppenderWorker, {IAppenderWorkerSettings} from "./node/worker/AppenderWorker";
import HttpWorker, {IHttpWorkerSettings} from "./node/worker/HttpWorker";
import HttpXmlParserWorker, {IHttpXmlParserWorkerSettings} from "./node/worker/HttpXmlParserWorker";
import JsonSplitterWorker, {IJsonSplitterWorkerSettings} from "./node/worker/JsonSplitterWorker";
import LimiterWorker from "./node/worker/LimiterWorker";
import NullWorker, {INullWorkerSettings} from "./node/worker/NullWorker";
import {default as ResequencerWorker, IResequencerWorkerSettings} from "./node/worker/ResequencerWorker";
import TestCaptureWorker from "./node/worker/TestCaptureWorker";
import UppercaseWorker from "./node/worker/UppercaseWorker";
import MultiProbeConnector from "./probe/MultiProbeConnector";
import Terminator from "./terminator/Terminator";
import INodeConfigProvider from "./topology/INodeConfigProvider";

class DIContainer extends Container {

    public static readonly WORKER_TYPE_WORKER = "worker";
    public static readonly WORKER_TYPE_SPLITTER = "splitter";

    constructor(private nodeConfigurator: INodeConfigProvider) {
        super();
        this.setServices();
        this.setWorkers();
    }

    private setServices() {
        this.set("amqp.connection", new Connection(amqpConnectionOptions, logger));

        // this.set("counter.storage", () => new InMemoryStorage());
        this.set("counter.storage", () => new RedisStorage(redisStorageOptions));

        this.set("probe.multi", new MultiProbeConnector(multiProbeOptions.host, multiProbeOptions.port));

        this.set("topology.terminator", (isMulti: boolean = false) => {
            if (isMulti) {
                return new Terminator(
                    topologyTerminatorOptions.port,
                    this.get("counter.storage")(),
                    this.get("probe.multi"),
                );
            }

            return new Terminator(
                topologyTerminatorOptions.port,
                this.get("counter.storage")(),
            );
        });

        this.set("metrics", (topology: string, node: string, measurement: string) => {
            return new Metrics(
                measurement,
                {topology_id: topology, node_id: node},
                metricsOptions.server,
                metricsOptions.port,
            );
        });

        this.set("limiter", new Limiter(
            new TcpClient(limiterOptions.host, limiterOptions.port),
            new LimiterPublisher(
                this.get("amqp.connection"),
                limiterOptions,
            ),
        ));

        this.set("counter.publisher", (settings: ICounterPublisherSettings) => {
            return new CounterPublisher(this.get("amqp.connection"), settings);
        });

        this.set("faucet.amqp", (settings: IAmqpFaucetSettings) => {
            return new AmqpFaucet(settings, this.get("amqp.connection"));
        });

        this.set("drain.amqp", (settings: IAmqpDrainSettings) => {
            const counterPub = this.get("counter.publisher")(settings);
            const followersPub = new FollowersPublisher(this.get("amqp.connection"), settings);
            const assertionPub = new AssertionPublisher(
                this.get("amqp.connection"),
                () => Promise.resolve(),
                { durable: persistentMode },
            );
            const metrics = this.get("metrics")(
                settings.node_label.topology_id,
                settings.node_label.id,
                metricsOptions.node_measurement,
            );

            return new AmqpDrain(settings, counterPub, followersPub, assertionPub, metrics);
        });
    }

    private setWorkers() {
        const wPrefix = DIContainer.WORKER_TYPE_WORKER;
        const sPrefix = DIContainer.WORKER_TYPE_SPLITTER;

        // Standard workers
        this.set(`${wPrefix}.appender`, (settings: IAppenderWorkerSettings) => {
            return new AppenderWorker(settings);
        });
        this.set(`${wPrefix}.http`, (settings: IHttpWorkerSettings) => {
            const metrics = this.get("metrics")(
                settings.node_label.topology_id,
                settings.node_label.id,
                metricsOptions.node_measurement,
            );

            return new HttpWorker(settings, metrics);
        });
        this.set(`${wPrefix}.http_limited`, (settings: IHttpWorkerSettings) => {
            return new LimiterWorker(
                this.get("limiter"),
                this.get(`${wPrefix}.http`)(settings),
                this.nodeConfigurator.getNodeConfig(settings.node_label.id, false).faucet,
            );
        });
        this.set(`${wPrefix}.http_xml_parser`, (settings: IHttpXmlParserWorkerSettings) => {
            const metrics = this.get("metrics")(
                settings.node_label.topology_id,
                settings.node_label.id,
                metricsOptions.node_measurement,
            );

            return new HttpXmlParserWorker(settings, metrics);
        });
        this.set(`${wPrefix}.null`, (settings: INullWorkerSettings) => {
            return new NullWorker(settings);
        });
        this.set(`${wPrefix}.resequencer`, (settings: IResequencerWorkerSettings) => {
            return new ResequencerWorker(settings);
        });
        this.set(`${wPrefix}.uppercase`, (settings: {}) => {
            return new UppercaseWorker();
        });

        // Splitter workers
        this.set(
            `${sPrefix}.amqprpc`,
            (settings: IAmqpWorkerSettings, fwd: IPartialForwarder, cps: ICounterPublisherSettings) => {
                return new AmqpNonBlockingWorker(
                    this.get("amqp.connection"),
                    settings,
                    fwd,
                    this.get("counter.publisher")(cps),
                );
            },
        );
        this.set(
            `${sPrefix}.json`,
            (settings: IJsonSplitterWorkerSettings, forwarder: IPartialForwarder, cps: ICounterPublisherSettings) => {
                return new JsonSplitterWorker(settings, forwarder);
            },
        );
        this.set(
            `${sPrefix}.amqprpc_limited`,
            (settings: IAmqpWorkerSettings, forwarder: IPartialForwarder, cps: ICounterPublisherSettings) => {
                return new LimiterWorker(
                    this.get("limiter"),
                    this.get(`${sPrefix}.amqprpc`)(settings, forwarder),
                    this.nodeConfigurator.getNodeConfig(settings.node_label.id, false).faucet,
                );
            },
        );

        // Test workers
        this.set(`${wPrefix}.capture`, (settings: {}) => {
            return new TestCaptureWorker();
        });
    }

}

export default DIContainer;
