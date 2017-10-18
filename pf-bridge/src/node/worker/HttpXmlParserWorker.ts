import JobMessage from "../../message/JobMessage";
import HttpWorker from "./HttpWorker";

class HttpXmlParserWorker extends HttpWorker {

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {string}
     */
    protected getHttpRequestBody(inMsg: JobMessage): string {
        return JSON.stringify({
            data: inMsg.getContent(),
        });
    }

}

export default HttpXmlParserWorker;
