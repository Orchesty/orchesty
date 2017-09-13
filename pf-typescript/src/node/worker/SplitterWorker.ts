import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

interface IJsonMessageFormat {
    data: any;
    settings: any;
}

class SplitterWorker implements IWorker {

    public processData(msg: JobMessage): Promise<JobMessage[]> {
        const output: JobMessage[] = [];

        try {
            const content: IJsonMessageFormat = JSON.parse(msg.getContent());

            if (content.data && Array.isArray(content.data) && content.data.length > 0) {
                let i: number = 1;
                content.data.forEach((item) => {
                    const splitContent: IJsonMessageFormat = {
                        data: item,
                        settings: content.settings,
                    };
                    const splitMsg = new JobMessage(
                        msg.getJobId(),
                        i,
                        msg.getHeaders(),
                        JSON.stringify(splitContent),
                        { status: ResultCode.SUCCESS, message: "Message split successfully."},
                    );
                    splitMsg.setReceivedTime(msg.getReceivedTime());

                    output.push(splitMsg);
                    i++;
                });
            }

        } catch (err) {
            output.splice(0, output.length);
            msg.setResult({
                status: ResultCode.INVALID_MESSAGE_CONTENT_FORMAT,
                message: `Invalid message content format. Error: ${err}`,
            });
            output.push(msg);
        }

        logger.info(
            `Worker[type"splitter"] processed message[id="${msg.getUuid()}]" \
            resultStatus="${msg.getResult().status}" resultMessage="${msg.getResult().message}"]`,
        );

        return Promise.resolve(output);
    }

}

export default SplitterWorker;
