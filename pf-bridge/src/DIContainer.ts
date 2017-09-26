import Container from "lib-nodejs/dist/src/container/Container";
import { default as Connection } from "lib-nodejs/dist/src/rabbitmq/Connection";
import {amqpConnectionOptions} from "./config";
import CounterPublisher from "./node/drain/amqp/CounterPublisher";
import FollowersPublisher from "./node/drain/amqp/FollowersPublisher";
import {default as AmqpDrain, IAmqpDrainSettings} from "./node/drain/AmqpDrain";
import {default as AmqpFaucet, IAmqpFaucetSettings} from "./node/faucet/AmqpFaucet";
import {default as HttpFaucet, IHttpFaucetSettings} from "./node/faucet/HttpFaucet";
import AmqpRpcWorker, {IAmqpRpcWorkerSettings} from "./node/worker/AmqpRpcWorker";
import AppenderWorker, {IAppenderWorkerSettings} from "./node/worker/AppenderWorker";
import HttpWorker, {IHttpWorkerSettings} from "./node/worker/HttpWorker";
import NullWorker from "./node/worker/NullWorker";
import SplitterWorker, {ISplitterWorkerSettings} from "./node/worker/SplitterWorker";
import UppercaseWorker from "./node/worker/UppercaseWorker";
import IPartialForwarder from "./node/drain/IPartialForwarder";
import Defaults from "./Defaults";

class DIContainer extends Container {

    constructor() {
        super();
        this.setServices();
        this.setFaucets();
        this.setDrains();
        this.setWorkers();
        this.setSplitterWorkers();
    }

    private setServices() {
        this.set("amqp.connection", new Connection(amqpConnectionOptions));
    }

    private setFaucets() {
        this.set("faucet.amqp", (settings: IAmqpFaucetSettings) => {
            return new AmqpFaucet(settings, this.get("amqp.connection"));
        });
        this.set("faucet.http", (settings: IHttpFaucetSettings) => {
            return new HttpFaucet(settings);
        });
    }

    private setDrains() {
        this.set("drain.amqp", (settings: IAmqpDrainSettings) => {
            const counterPubl = new CounterPublisher(this.get("amqp.connection"), settings);
            const followersPub = new FollowersPublisher(this.get("amqp.connection"), settings);

            return new AmqpDrain(settings, counterPubl, followersPub);
        });
    }

    private setWorkers() {
        this.set("worker.appender", (settings: IAppenderWorkerSettings) => {
            return new AppenderWorker(settings);
        });
        this.set("worker.http", (settings: IHttpWorkerSettings) => {
            return new HttpWorker(settings);
        });
        this.set("worker.null", (settings: {}) => {
            return new NullWorker();
        });
        this.set("worker.uppercase", (settings: {}) => {
            return new UppercaseWorker();
        });
    }

    private setSplitterWorkers() {
        this.set("splitter.amqprpc", (settings: IAmqpRpcWorkerSettings) => {
            return new AmqpRpcWorker(this.get("amqp.connection"), settings);
        });
        this.set("splitter.json", (settings: ISplitterWorkerSettings, partialForwarder: IPartialForwarder) => {
            return new SplitterWorker(settings, partialForwarder);
        });
    }

}

export default DIContainer;
