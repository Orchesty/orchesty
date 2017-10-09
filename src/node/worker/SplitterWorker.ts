import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IPartialForwarder from "../drain/IPartialForwarder";
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

    /**
     *
     * @param {ISplitterWorkerSettings} settings
     * @param {IPartialForwarder} partialForwarder
     */
    constructor(
        private settings: ISplitterWorkerSettings,
        private partialForwarder: IPartialForwarder,
    ) {}

    /**
     * Splits the the JSON data in the content into separate messages
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {

        let content: IJsonMessageFormat;

        try {
            content = JSON.parse(msg.getContent());
        } catch (err) {
            this.setError(msg, "Could not parse message content.", err);
            return Promise.resolve(msg);
        }

        if (!content.hasOwnProperty('data') || !content.hasOwnProperty('settings')) {
            this.setError(msg, "Cannot split content, data and/or settings key is missing.", null);
            return Promise.resolve(msg);
        }

        if (!Array.isArray(content.data) || content.data.length < 1) {
            this.setError(msg, "Cannot split content. data is not array or is empty.", null);
            return Promise.resolve(msg);
        }

        return this.splitAndSendParts(msg, content)
            .then((splits: void[]) => {
                msg.setForwardSelf(false);
                msg.setMultiplier(splits.length);
                msg.setResult({
                    code: ResultCode.SUCCESS,
                    message: `Message split into ${splits.length} partial messages was successful.`,
                });

                logger.info(
                    `Worker[type"splitter"] split message. \
                    Status="${msg.getResult().code}" message="${msg.getResult().message}"]`,
                    logger.ctxFromMsg(msg),
                );

                return msg;
            })
            .catch(() => {
                this.setError(msg, "One or multiple partial messages forward failed", {});
                msg.setForwardSelf(false);

                return msg;
            });
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
     * @param {string} message
     * @param err
     */
    private setError(msg: JobMessage, message: string, err: any): void {
        msg.setResult({ code: ResultCode.INVALID_CONTENT, message });

        logger.warn(
            `Worker[type'splitter'] could not parse json message. ${msg.getResult().message}`,
            logger.ctxFromMsg(msg, err),
        );
    }

    /**
     *
     * @param {JobMessage} msg
     * @param {IJsonMessageFormat} content
     * @return {JobMessage}
     */
    private splitAndSendParts(msg: JobMessage, content: IJsonMessageFormat): Promise<void[]> {
        const splitPromises: Array<Promise<void>> = [];
        let i: number = 1;

        content.data.forEach((item: any) => {
            const splitContent: IJsonMessageFormat = {
                data: item,
                settings: content.settings,
            };
            const splitMsg = new JobMessage(
                this.settings.node_id,
                msg.getCorrelationId(),
                msg.getProcessId(),
                msg.getParentId(),
                i,
                JSON.parse(JSON.stringify(msg.getHeaders())), // simple object cloning
                new Buffer(JSON.stringify(splitContent)),
                { code: ResultCode.SUCCESS, message: `Split ${i}/${content.data.length}.`},
            );

            splitPromises.push(this.partialForwarder.forwardPart(splitMsg));

            i++;
        });

        return Promise.all(splitPromises);
    }

}

export default SplitterWorker;
