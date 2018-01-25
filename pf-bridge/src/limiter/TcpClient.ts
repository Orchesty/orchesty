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

            client.connect(this.port, this.host, () => {
                logger.info(`Tcp listener sending: ${content}`);
                client.write(new Buffer(content));
            });

            client.on("data", (data: Buffer) => {
                client.destroy();
                logger.info(`Tcp listener received: ${data.toString()}`);
                resolve(data.toString());
            });

            client.on("close", () => {
                //
            });

            client.on("error", (e: any) => {
                reject(e);
            });
        });
    }

}
