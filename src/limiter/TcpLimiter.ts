import * as net from "net";
import * as uuid4 from "uuid/v4";
import logger from "../logger/Logger";
import Headers from "../message/Headers";
import JobMessage from "../message/JobMessage";
import ILimiter from "./ILimiter";

const HEALTH_CHECK_REQUEST = "pf-health-check";
const HEALTH_CHECK_VALID_RESPONSE = "ok";

const LIMIT_CHECK_PREFIX = "pf-check";
const LIMIT_CHECK_RESPONSE_FREE = "ok";

export interface ITcpLimiterSettings {
    host: string;
    port: number;
}

/**
 * Fake temp limiter
 */
export default class TcpLimiter implements ILimiter {

    constructor(private settings: ITcpLimiterSettings) {}

    /**
     * Local fake limiter always returns true
     *
     * @return {Promise<boolean>}
     */
    public async isReady(): Promise<boolean> {
        try {
            const resp = await this.sendOverTcp(HEALTH_CHECK_REQUEST);

            if (resp === HEALTH_CHECK_VALID_RESPONSE) {
                return true;
            } else {
                logger.warn("TcpLimiter isReady - limiter not responding");
                return false;
            }
        } catch (e) {
            logger.error("TcpLimiter isReady error:", {error: e});
            return false;
        }
    }

    /**
     * Always returns true
     *
     * @param {JobMessage} msg
     * @return {Promise<boolean>}
     */
    public async canBeProcessed(msg: JobMessage): Promise<boolean> {
        // If limit headers are missing, allow processing it
        if (!msg.getHeaders().hasPFHeader(Headers.LIMIT_KEY) ||
            !msg.getHeaders().hasPFHeader(Headers.LIMIT_TIME) ||
            !msg.getHeaders().hasPFHeader(Headers.LIMIT_VALUE)
        ) {
            return false;
        }

        // Send request and wait for tcp limiter response
        const reqId = uuid4();
        const key = msg.getHeaders().getPFHeader(Headers.LIMIT_KEY);
        const time = msg.getHeaders().getPFHeader(Headers.LIMIT_TIME);
        const value = msg.getHeaders().getPFHeader(Headers.LIMIT_VALUE);
        const content = `${LIMIT_CHECK_PREFIX};${reqId};${key};${time};${value}`;

        const result = await this.sendOverTcp(content);

        if (result === LIMIT_CHECK_RESPONSE_FREE) {
            return true;
        }
        return false;
    }

    /**
     * This should be never called
     *
     * @param {JobMessage} msg
     * @return {Promise<void>}
     */
    public async postpone(msg: JobMessage): Promise<void> {
        return;
    }

    // TODO - reuse single Socket
    private sendOverTcp(content: string): Promise<string> {
        content = content + "\n";
        return new Promise((resolve, reject) => {
            const client = new net.Socket();

            client.connect(this.settings.port, this.settings.host, () => {
                logger.info(`Tcp listener sending: ${content}`);
                client.write(content);
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
