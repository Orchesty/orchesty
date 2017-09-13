import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

interface IJsonMessageFormat {
    data: any;
    settings: any;
}

class SplitterWorker implements IWorker {

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
                `Worker[type"splitter"] split message[id="${msg.getUuid()}]" \
                resultStatus="${msg.getResult().status}" resultMessage="${msg.getResult().message}"]`,
            );

            return Promise.resolve(msg);

        } catch (err) {
            msg.setResult({
                status: ResultCode.INVALID_MESSAGE_CONTENT_FORMAT,
                message: `Invalid message content format that cannot be split. Error: ${err}`,
            });

            logger.warn(`Worker[type="splitter"] could not split message. Err: ${msg.getResult().message}`);

            return Promise.resolve(msg);
        }
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
