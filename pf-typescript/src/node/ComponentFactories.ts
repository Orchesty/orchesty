import { default as Connection } from "lib-nodejs/dist/src/rabbitmq/Connection";
import Container from "../Container";
import CounterPublisher from "./drain/amqp/CounterPublisher";
import FollowersPublisher from "./drain/amqp/FollowersPublisher";
import {default as AMQPDrain, IAMQPDrainSettings} from "./drain/AMQPDrain";
import {default as AMQPFaucet, IAMQPFaucetSettings} from "./faucet/AMQPFaucet";
import {default as HttpFaucet, IHttpFaucetSettings} from "./faucet/HttpFaucet";
import AppenderWorker, {IAppenderWorkerSettings} from "./worker/AppenderWorker";
import HttpWorker, {IHttpWorkerSettings} from "./worker/HttpWorker";
import NullWorker from "./worker/NullWorker";
import UppercaseWorker from "./worker/UppercaseWorker";

class ComponentFactories extends Container {

    constructor(private amqpConn: Connection) {
        super();
        this.setFaucets();
        this.setDrains();
        this.setWorkers();
    }

    private setFaucets() {
        this.set("faucet.amqp", (settings: IAMQPFaucetSettings) => {
            return new AMQPFaucet(settings, this.amqpConn);
        });
        this.set("faucet.http", (settings: IHttpFaucetSettings) => {
            return new HttpFaucet(settings);
        });
    }

    private setDrains() {
        this.set("drain.amqp", (settings: IAMQPDrainSettings) => {
            const counterPubl = new CounterPublisher(this.amqpConn, settings);
            const followersPub = new FollowersPublisher(this.amqpConn, settings);

            return new AMQPDrain(settings, counterPubl, followersPub);
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

}

export default ComponentFactories;
