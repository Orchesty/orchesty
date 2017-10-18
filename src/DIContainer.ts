import Container from "lib-nodejs/dist/src/container/Container";
import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import { default as Connection } from "lib-nodejs/dist/src/rabbitmq/Connection";
import {amqpConnectionOptions} from "./config";
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

    constructor() {
        super();
        this.setServices();
        this.setWorkers();
    }

    private setServices() {
        this.set("amqp.connection", new Connection(amqpConnectionOptions));

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

            return new AmqpDrain(settings, counterPub, followersPub, assertionPub);
        });
    }

    private setWorkers() {
        // Standard workers
        this.set("worker.appender", (settings: IAppenderWorkerSettings) => {
            return new AppenderWorker(settings);
        });
        this.set("worker.http", (settings: IHttpWorkerSettings) => {
            return new HttpWorker(settings);
        });
        this.set("worker.http_xml_parser", (settings: IHttpXmlParserWorkerSettings) => {
            return new HttpXmlParserWorker(settings);
        });
        this.set("worker.null", (settings: {}) => {
            return new NullWorker();
        });
        this.set("worker.uppercase", (settings: {}) => {
            return new UppercaseWorker();
        });

        // Splitter workers
        this.set("splitter.amqprpc", (settings: IAmqpRpcWorkerSettings, forwarder: IPartialForwarder) => {
            return new AmqpRpcWorker(this.get("amqp.connection"), settings, forwarder);
        });
        this.set("splitter.json", (settings: ISplitterWorkerSettings, forwarder: IPartialForwarder) => {
            return new SplitterWorker(settings, forwarder);
        });
    }

}

export default DIContainer;
