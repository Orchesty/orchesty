import * as net from "net";
import logger from "../logger/Logger";

/**
 * TODO - refactor to use single net.client for all requests
 */
export default class TcpClient {

    constructor(private host: string, private port: number) {}

    /**
     *
     * @param {string} content
     * @returns {Promise<string>}
     */
    public send(content: string): Promise<string> {
        content = content + "\n";

        return new Promise((resolve, reject) => {
            const client = new net.Socket();

            const timeout = setTimeout(() => {
                logger.error("TcpClient: timeout reached")
                reject();
            }, 30000);

            client.connect(this.port, this.host, () => {
                logger.info(`Tcp listener sending: ${content}`);
                client.write(Buffer.from(content));
            });

            client.on("data", (data: Buffer) => {
                clearInterval(timeout);
                client.destroy();
                logger.info(`Tcp listener received: ${data.toString()}`);
                resolve(data.toString());
            });

            client.on("end", () => {
                clearInterval(timeout);
                logger.info("TcpClient: end event - rejected")
            });

            client.on("close", () => {
                clearInterval(timeout);
                logger.info("TcpClient: close event")
            });

            client.on("error", (e: Error) => {
                clearInterval(timeout);
                logger.error(`TcpClient: error event. Message: "${e.message}"`)
                reject(e);
            });
        });
    }

}
