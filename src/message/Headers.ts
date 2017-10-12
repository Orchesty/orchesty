
// Header prefixes
const PF_HEADERS_PREFIX           = "pf_";
const PF_PERMANENT_HEADERS_PREFIX = "pfp_";

// Mandatory PF headers
export const CORRELATION_ID_HEADER  = "pfp_correlation_id";
export const PROCESS_ID_HEADER      = "pfp_process_id";
export const PARENT_ID_HEADER       = "pfp_parent_id";
export const SEQUENCE_ID_HEADER     = "pfp_sequence_id";

export const HEADERS_WHITELIST = [
    "content-type",
];

export interface IMandatoryHeaders {
    pfp_correlation_id: string;
    pfp_process_id: string;
    pfp_parent_id: string;
    pfp_sequence_id: number;
}

class Headers {

    /**
     *
     * @return {{[p: string]: string}}
     * @param headers
     */
    public static getPFHeaders(headers: { [key: string]: any }): { [key: string]: string } {
        const pfHeaders: any = {};

        Object.keys(headers).forEach((key: any) => {
            if (key.substr(0, PF_PERMANENT_HEADERS_PREFIX.length) === PF_PERMANENT_HEADERS_PREFIX) {
                pfHeaders[key] = headers[key];
                return;
            }

            if (key.substr(0, PF_HEADERS_PREFIX.length) === PF_HEADERS_PREFIX) {
                pfHeaders[key] = headers[key];
                return;
            }

            if (HEADERS_WHITELIST.indexOf(key) > -1) {
                pfHeaders[key] = headers[key];
                return;
            }
        });

        return pfHeaders;
    }

    /**
     *
     * @param headers
     */
    public static validateMandatoryHeaders(headers: { [key: string]: any }) {
        if (!headers) {
            throw new Error("Invalid headers. Is it object?");
        }

        if (!headers[CORRELATION_ID_HEADER]) {
            throw new Error(`Invalid '${CORRELATION_ID_HEADER}' header.`);
        }

        if (!headers[PROCESS_ID_HEADER]) {
            throw new Error(`Invalid '${PROCESS_ID_HEADER}' header.`);
        }

        if (headers[PARENT_ID_HEADER] === undefined) {
            throw new Error(`Invalid '${PARENT_ID_HEADER}' header.`);
        }

        if (!headers[SEQUENCE_ID_HEADER]) {
            throw new Error(`Invalid '${SEQUENCE_ID_HEADER}' header.`);
        }
    }

    /**
     *
     * @param headers
     * @return {boolean}
     */
    public static containsAllMandatory(headers: { [key: string]: any }): boolean {
        try {
            Headers.validateMandatoryHeaders(headers);
            return true;
        } catch (err) {
            return false;
        }
    }

    /**
     *
     * @param {{[p: string]: any}} headers
     */
    constructor(private headers?: { [key: string]: any }) {}

    /**
     *
     * @param {string} key
     * @param value
     */
    public setHeader(key: string, value: any) {
        this.headers[`${PF_HEADERS_PREFIX}key`] = value;
    }

    /**
     *
     * @param {string} key
     * @param value
     */
    public setPermanentHeader(key: string, value: any) {
        this.headers[`${PF_PERMANENT_HEADERS_PREFIX}}key`] = value;
    }

    /**
     *
     * @param {string} key
     * @param value
     */
    public setCustomHeader(key: string, value: any) {
        this.headers[key] = value;
    }

    /**
     *
     * @return any
     */
    public getRaw(): any {
        return this.headers;
    }

    /**
     *
     * @param {string} key
     */
    public removeHeader(key: string) {
        delete this.headers[`${PF_HEADERS_PREFIX}key`];
    }

    /**
     *
     * @param {string} key
     */
    public removeCustomHeader(key: string) {
        delete this.headers[key];
    }

}

export default Headers;
