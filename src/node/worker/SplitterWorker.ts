import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import IPartialForwarder from "../drain/IPartialForwarder";
import IWorker from "./IWorker";
import Resequencer from "../Resequencer";

export interface ISplitterWorkerSettings {
    node_label: INodeLabel;
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

        let content: any[];

        try {
            content = JSON.parse(msg.getContent());
        } catch (err) {
            this.setError(msg, "Could not parse message content. Is it valid JSON?", err);
            return Promise.resolve(msg);
        }

        if (!Array.isArray(content) || content.length < 1) {
            this.setError(msg, "Message content must be json array.", null);
            return Promise.resolve(msg);
        }

        return this.splitAndSendParts(msg, content)
            .then((splits: void[]) => {
                msg.setForwardSelf(false);
                msg.setMultiplier(splits.length);
                msg.setResult({
                    code: ResultCode.SUCCESS,
                    message: `Split into ${splits.length} messages was successful.`,
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
     * @param {any[]} content
     * @return {Promise<void[]>}
     */
    private splitAndSendParts(msg: JobMessage, content: any[]): Promise<void[]> {
        const splitPromises: Array<Promise<void>> = [];
        let i: number = Resequencer.START_SEQUENCE_ID;

        content.forEach((item: any) => {
            const headers = new Headers(msg.getHeaders().getRaw());
            headers.setPFHeader(Headers.SEQUENCE_ID, `${i}`);
            headers.setHeader("content-type", "application/json");

            const splitMsg = new JobMessage(
                this.settings.node_label,
                headers.getRaw(),
                new Buffer(JSON.stringify(item)),
                { code: ResultCode.SUCCESS, message: `Json split ${i}/${content.length - 1}.`},
            );

            splitPromises.push(this.partialForwarder.forwardPart(splitMsg));

            i++;
        });

        return Promise.all(splitPromises);
    }

}

export default SplitterWorker;
