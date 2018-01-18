import * as uuid4 from "uuid/v4";
import logger from "../logger/Logger";
import Headers from "../message/Headers";
import JobMessage from "../message/JobMessage";
import LimiterPublisher from "./amqp/LimiterPublisher";
import ILimiter from "./ILimiter";
import TcpClient from "./TcpClient";

const HEALTH_CHECK_PREFIX = "pf-health-check";
const HEALTH_CHECK_VALID_RESPONSE = "ok";

const LIMIT_CHECK_PREFIX = "pf-check";
const LIMIT_CHECK_RESPONSE_FREE = "ok";

export interface ILimiterSettings {
    host: string;
    port: number;
    queue: {
        name: string;
        options: any;
    };
}

/**
 * Fake temp limiter
 */
export default class Limiter implements ILimiter {

    /**
     *
     * @returns {string}
     */
    private static createHealthCheckRequest(): string {
        const reqId = uuid4();

        return `${HEALTH_CHECK_PREFIX};${reqId}`;
    }

    /**
     *
     * @param {JobMessage} msg
     * @returns {string}
     */
    private static createCheckLimitRequest(msg: JobMessage): string {
        // Send request and wait for tcp limiter response
        const reqId = uuid4();
        const key = msg.getHeaders().getPFHeader(Headers.LIMIT_KEY);
        const time = msg.getHeaders().getPFHeader(Headers.LIMIT_TIME);
        const value = msg.getHeaders().getPFHeader(Headers.LIMIT_VALUE);

        return `${LIMIT_CHECK_PREFIX};${reqId};${key};${time};${value}`;
    }

    constructor(
        private tcpClient: TcpClient,
        private publisher: LimiterPublisher,
    ) {}

    /**
     * Local fake limiter always returns true
     *
     * @return {Promise<boolean>}
     */
    public async isReady(): Promise<boolean> {
        try {
            const content = Limiter.createHealthCheckRequest();
            const resp = await this.tcpClient.send(content);
            const result = resp.split(";");

            if (result.length === 3 && result[2] === HEALTH_CHECK_VALID_RESPONSE) {
                return true;
            } else {
                logger.warn(`TcpLimiter limiter not ready. Resp: ${resp}`);
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
        // If limit headers are missing, allow processing it directly because limiter could not decide without them
        if (!msg.getHeaders().hasPFHeader(Headers.LIMIT_KEY) ||
            !msg.getHeaders().hasPFHeader(Headers.LIMIT_TIME) ||
            !msg.getHeaders().hasPFHeader(Headers.LIMIT_VALUE)
        ) {
            return true;
        }

        try {
            const content = Limiter.createCheckLimitRequest(msg);
            const resp = await this.tcpClient.send(content);
            const result = resp.split(";");

            if (result.length === 3 && result[2] === LIMIT_CHECK_RESPONSE_FREE) {
                return true;
            }

            return false;
        } catch (e) {
            logger.error("TcpLimiter can be processed error:", {error: e});
            // We do not know the limiter result allow processing

            return true;
        }
    }

    /**
     * This should be never called
     *
     * @param {JobMessage} msg
     * @return {Promise<void>}
     */
    public async postpone(msg: JobMessage): Promise<void> {
        if (!msg.getHeaders().hasPFHeader(Headers.LIMIT_RETURN_ROUTING_KEY) ||
            !msg.getHeaders().hasPFHeader(Headers.LIMIT_RETURN_EXCHANGE)
        ) {
            throw new Error("To postpone a message to limiter, it must contain limit-return-* headers.");
        }

        return this.publisher.send(msg);
    }

}
