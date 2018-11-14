import * as http from "http";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import * as request from "request";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import AWorker from "./AWorker";
import Headers from "../../message/Headers";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {Channel} from "amqplib";
import logger from "../../logger/Logger";

export interface ILongRunningWorkerSettings {
    node_label: INodeLabel;
    host: string;
    port: number;
    method: string;
    process_path: string;
    status_path: string;
    secure: boolean;
    opts: any;
}

/**
 * Converts JobMessage to Http request and then converts received Http response back to JobMessage object
 */
class LongRunningWorker extends AWorker {

    private timeout: number;
    private agent: http.Agent;

    private publisher: Publisher;
    private resultsQueue: { name: string, options: any, prefetch: number };

    constructor(
        protected settings: ILongRunningWorkerSettings,
        protected connection: Connection,
        protected metrics: IMetrics,
    ) {
        super();

        this.timeout = 60000;
        this.agent = new http.Agent({ keepAlive: true, maxSockets: Infinity });

        this.resultsQueue = {
            name: 'pipes.long-running',
            options: { durable: true, exclusive: false, autoDelete: false },
            prefetch: 1,
        };

        const publisherPrepare = async (ch: Channel): Promise<void> => {
            await ch.assertQueue(this.resultsQueue.name, this.resultsQueue.options);
        };

        this.publisher = new Publisher(connection, publisherPrepare);
    }

    /**
     * Processes service type messages
     *
     * @param {JobMessage} msg
     * @return {JobMessage}
     */
    public async processService(msg: JobMessage): Promise<JobMessage> {
        msg.setResult({code: ResultCode.SUCCESS, message: "Service message passed by."});

        return msg;
    }

    /**
     * Processes process type messages
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public async processData(msg: JobMessage): Promise<JobMessage[]> {
        const headers = msg.getHeaders();
        const doc = headers.hasPFHeader(Headers.DOCUMENT_ID);

        logger.error("Worker[type='long_running'] 01.");

        if (doc) {
            logger.error("Worker[type='long_running'] doc: " + doc);
            const reqParams = { method: "GET", url: this.getUrl(this.settings.process_path)};
            request(reqParams);
            logger.error("Worker[type='long_running'] url:  " + reqParams.url);
            headers.removePFHeader(Headers.DOCUMENT_ID);
            msg.setHeaders(headers);

            logger.error("Worker[type='long_running'] res.");
        } else {
            logger.error("Worker[type='long_running'] queue.");
            this.publisher.sendToQueue(
                this.resultsQueue.name,
                new Buffer(msg.getContent()),
                {
                    replyTo: this.resultsQueue.name,
                    headers: msg.getHeaders().getRaw(),
                },
            );
        }

        return [msg];
    }

    /**
     * Returns whether the worker is fully ready
     *
     * @return {Promise<boolean>}
     */
    public async isWorkerReady(): Promise<boolean> {
        return true;
    }

    /**
     *
     * @param {string} path
     * @return {string}
     */
    private getUrl(path: string): string {
        const protocol = this.settings.secure ? "https://" : "http://";
        const port = this.settings.port || 80;

        return `${protocol}${this.settings.host}:${port}${path}`;
    }

}

export default LongRunningWorker;
