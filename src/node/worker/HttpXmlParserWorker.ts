import JobMessage from "../../message/JobMessage";
import HttpWorker from "./HttpWorker";

class HttpXmlParserWorker extends HttpWorker {

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {string}
     */
    protected getHttpRequestBody(inMsg: JobMessage): any {
        return {
            data: JSON.stringify(inMsg.getContent()),
        };
    }

}

export default HttpXmlParserWorker;
