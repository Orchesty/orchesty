
interface IMessage {

    getHeaders(): {};
    getContent(): string;
    getNodeId(): string;
    getCorrelationId(): string;
    getProcessId(): string;
    getParentId(): string;

}

export default IMessage;
