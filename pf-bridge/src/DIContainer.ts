import {AssertionPublisher} from "amqplib-plus/dist/lib/AssertPublisher";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Container} from "hb-utils/dist/lib/Container";
import {Metrics} from "metrics-sender/dist/lib/metrics/Metrics";
import {
    amqpConnectionOptions, metricsOptions, multiProbeOptions, redisStorageOptions,
    topologyTerminatorOptions,
} from "./config";
import RedisStorage from "./counter/storage/RedisStorage";
import FakeLimiter from "./limiter/FakeLimiter";
import CounterPublisher from "./node/drain/amqp/CounterPublisher";
import FollowersPublisher from "./node/drain/amqp/FollowersPublisher";
import {default as AmqpDrain, IAmqpDrainSettings} from "./node/drain/AmqpDrain";
import IPartialForwarder from "./node/drain/IPartialForwarder";
import {default as AmqpFaucet, IAmqpFaucetSettings} from "./node/faucet/AmqpFaucet";
import AmqpRpcWorker, {IAmqpRpcWorkerSettings} from "./node/worker/AmqpRpcWorker";
import AppenderWorker, {IAppenderWorkerSettings} from "./node/worker/AppenderWorker";
import HttpWorker, {IHttpWorkerSettings} from "./node/worker/HttpWorker";
import HttpXmlParserWorker, {IHttpXmlParserWorkerSettings} from "./node/worker/HttpXmlParserWorker";
import LimiterWorker from "./node/worker/LimiterWorker";
import NullWorker from "./node/worker/NullWorker";
import {default as ResequencerWorker, IResequencerWorkerSettings} from "./node/worker/ResequencerWorker";
import SplitterWorker, {ISplitterWorkerSettings} from "./node/worker/SplitterWorker";
import TestCaptureWorker from "./node/worker/TestCaptureWorker";
import UppercaseWorker from "./node/worker/UppercaseWorker";
import MultiProbeConnector from "./probe/MultiProbeConnector";
import Terminator from "./terminator/Terminator";

class DIContainer extends Container {

    public static readonly WORKER_TYPE_WORKER = "worker";
    public static readonly WORKER_TYPE_SPLITTER = "splitter";

    constructor() {
        super();
        this.setServices();
        this.setWorkers();
    }

    private setServices() {
        this.set("amqp.connection", new Connection(amqpConnectionOptions));

        // this.set("counter.storage", new InMemoryStorage());
        this.set("counter.storage", new RedisStorage(redisStorageOptions));

        this.set("probe.multi", new MultiProbeConnector(multiProbeOptions.host, multiProbeOptions.port));

        this.set("topology.terminator", (isMulti: boolean = false) => {
            if (isMulti) {
                return new Terminator(
                    topologyTerminatorOptions.port,
                    this.get("counter.storage"),
                    this.get("probe.multi"),
                );
            }

            return new Terminator(
                topologyTerminatorOptions.port,
                this.get("counter.storage"),
            );
        });

        this.set("metrics", (topology: string, node: string) => {
            return new Metrics(
                metricsOptions.node_measurement,
                {topology_id: topology, node_id: node},
                metricsOptions.server,
                metricsOptions.port,
            );
        });

        this.set("faucet.amqp", (settings: IAmqpFaucetSettings) => {
            return new AmqpFaucet(settings, this.get("amqp.connection"));
        });

        this.set("drain.amqp", (settings: IAmqpDrainSettings) => {
            const counterPub = new CounterPublisher(this.get("amqp.connection"), settings);
            const followersPub = new FollowersPublisher(this.get("amqp.connection"), settings);
            const assertionPub = new AssertionPublisher(
                this.get("amqp.connection"),
                () => Promise.resolve(),
                {},
            );
            const metrics = this.get("metrics")(settings.node_label.topology_id, settings.node_label.id);

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
            const metrics = this.get("metrics")(settings.node_label.topology_id, settings.node_label.id);

            return new HttpWorker(settings, metrics);
        });
        this.set(`${wPrefix}.http_xml_parser`, (settings: IHttpXmlParserWorkerSettings) => {
            const metrics = this.get("metrics")(settings.node_label.topology_id, settings.node_label.id);

            return new HttpXmlParserWorker(settings, metrics);
        });
        this.set(`${wPrefix}.limited_http`, (settings: IHttpWorkerSettings) => {
            return new LimiterWorker(
                new FakeLimiter(),
                this.get(`${wPrefix}.http`)(settings),
            );
        });
        this.set(`${wPrefix}.null`, (settings: {}) => {
            return new NullWorker();
        });
        this.set(`${wPrefix}.resequencer`, (settings: IResequencerWorkerSettings) => {
            return new ResequencerWorker(settings);
        });
        this.set(`${wPrefix}.uppercase`, (settings: {}) => {
            return new UppercaseWorker();
        });

        // Splitter workers
        this.set(`${sPrefix}.amqprpc`, (settings: IAmqpRpcWorkerSettings, forwarder: IPartialForwarder) => {
            return new AmqpRpcWorker(this.get("amqp.connection"), settings, forwarder);
        });
        this.set(`${sPrefix}.json`, (settings: ISplitterWorkerSettings, forwarder: IPartialForwarder) => {
            return new SplitterWorker(settings, forwarder);
        });

        // Test workers
        this.set(`${wPrefix}.capture`, (settings: {}) => {
            return new TestCaptureWorker();
        });
    }

}

export default DIContainer;
