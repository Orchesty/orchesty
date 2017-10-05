
interface IMessage {

    getHeaders(): {};
    getBody(): Buffer;

    getNodeId(): string;
    getCorrelationId(): string;
    getProcessId(): string;
    getParentId(): string;

}

export default IMessage;
