import Container from "lib-nodejs/dist/src/container/Container";
import Metrics from "lib-nodejs/dist/src/metrics/Metrics";
import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import { default as Connection } from "lib-nodejs/dist/src/rabbitmq/Connection";
import {amqpConnectionOptions, metricsOptions} from "./config";
import CounterPublisher from "./node/drain/amqp/CounterPublisher";
import FollowersPublisher from "./node/drain/amqp/FollowersPublisher";
import {default as AmqpDrain, IAmqpDrainSettings} from "./node/drain/AmqpDrain";
import IPartialForwarder from "./node/drain/IPartialForwarder";
import {default as AmqpFaucet, IAmqpFaucetSettings} from "./node/faucet/AmqpFaucet";
import AmqpRpcWorker, {IAmqpRpcWorkerSettings} from "./node/worker/AmqpRpcWorker";
import AppenderWorker, {IAppenderWorkerSettings} from "./node/worker/AppenderWorker";
import HttpWorker, {IHttpWorkerSettings} from "./node/worker/HttpWorker";
import HttpXmlParserWorker, {IHttpXmlParserWorkerSettings} from "./node/worker/HttpXmlParserWorker";
import NullWorker from "./node/worker/NullWorker";
import SplitterWorker, {ISplitterWorkerSettings} from "./node/worker/SplitterWorker";
import UppercaseWorker from "./node/worker/UppercaseWorker";

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

        this.set("metrics", (topology: string, node: string) => {
            return new Metrics(
                metricsOptions.node_measurement,
                topology,
                node,
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
                () =>  Promise.resolve(),
                {},
            );
            const metrics = this.get("metrics")(settings.node_label.id);

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
            return new HttpWorker(settings);
        });
        this.set(`${wPrefix}.http_xml_parser`, (settings: IHttpXmlParserWorkerSettings) => {
            return new HttpXmlParserWorker(settings);
        });
        this.set(`${wPrefix}.null`, (settings: {}) => {
            return new NullWorker();
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
    }

}

export default DIContainer;
