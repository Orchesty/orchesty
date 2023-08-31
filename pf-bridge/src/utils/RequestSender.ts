import {UrlOptions} from "request";
import * as request from "request";
import logger from "../logger/Logger";

export default class RequestSender {

    /**
     *
     * @param {request.UrlOptions} options
     */
    public static send(options: UrlOptions): void {
        logger.debug(`Sending request to: ${options.url}`);
        request(options, (err, response) => {
            if (err) {
                logger.error(`Request to ${options.url} ended with error: ${err.message}`);
                return;
            }

            if (response.statusCode !== 200) {
                const code = response.statusCode;
                logger.error(
                    `Request to ${options.url} resulted with statusCode: ${code}.`,
                    {data: JSON.stringify(response.body)},
                );
                return;
            }

            logger.debug(`Request to: ${options.url} OK.`);
        });
    }

}