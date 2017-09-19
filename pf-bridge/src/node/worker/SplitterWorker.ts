import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

interface IJsonMessageFormat {
    data: any;
    settings: any;
}

export interface ISplitterWorkerSettings {
    node_id: string;
}

/**
 *
 */
class SplitterWorker implements IWorker {

    constructor(private settings: ISplitterWorkerSettings) {}

    /**
     * Splits the the JSON data in the content into separate messages
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {

        try {
            const content: IJsonMessageFormat = JSON.parse(msg.getContent());

            if (!content.data || !content.settings) {
                throw new Error("Cannot split content, data and/or settings key is missing.");
            }

            if (!Array.isArray(content.data) || content.data.length < 1) {
                throw new Error("Cannot split content. data is not array or is empty.");
            }

            msg = this.split(msg, content);

            logger.info(
                `Worker[type"splitter"] split message. \
                Status="${msg.getResult().status}" message="${msg.getResult().message}"]`,
                {node_id: this.settings.node_id, correlation_id: msg.getJobId()},
            );

            return Promise.resolve(msg);

        } catch (err) {
            msg.setResult({
                status: ResultCode.INVALID_MESSAGE_CONTENT_FORMAT,
                message: `Invalid message content format that cannot be split. Error: ${err}`,
            });

            logger.warn(
                "Worker[type'splitter'] could not split message.",
                {node_id: this.settings.node_id, correlation_id: msg.getJobId(), error: err},
            );

            return Promise.resolve(msg);
        }
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        return Promise.resolve(true);
    }

    /**
     *
     * @param {JobMessage} msg
     * @param {IJsonMessageFormat} content
     * @return {JobMessage}
     */
    private split(msg: JobMessage, content: IJsonMessageFormat): JobMessage {
        const splitSet: JobMessage[] = [];
        let i: number = 1;

        content.data.forEach((item: any) => {
            const splitContent: IJsonMessageFormat = {
                data: item,
                settings: content.settings,
            };
            const splitMsg = new JobMessage(
                msg.getJobId(),
                i,
                JSON.parse(JSON.stringify(msg.getHeaders())), // simple object cloning
                JSON.stringify(splitContent),
                { status: ResultCode.SUCCESS, message: `Split ${i}/${content.data.length}.`},
            );
            splitMsg.setReceivedTime(msg.getReceivedTime());

            splitSet.push(splitMsg);
            i++;
        });

        msg.setSplit(splitSet);
        msg.setResult({
            status: ResultCode.SUCCESS,
            message: `Message split into ${splitSet.length} parts was successful.`,
        });

        return msg;
    }

}

export default SplitterWorker;
