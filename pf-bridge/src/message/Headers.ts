import {PFHeaders} from "./HeadersEnum";

const PF_HEADERS_PREFIX           = "pf_";

export const HEADERS_WHITELIST = [
    "content-type",
];

class Headers {

    /**
     *
     * @return {{[p: string]: string}}
     * @param headers
     */
    public static getPFHeaders(headers: { [key: string]: any }): { [key: string]: string } {
        const pfHeaders: any = {};

        Object.keys(headers).forEach((key: any) => {
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
    public static validateMandatoryHeaders(headers: { [key: string]: string }) {
        if (!headers) {
            throw new Error("Invalid headers. Is it object?");
        }

        if (!headers[`${PF_HEADERS_PREFIX}${PFHeaders.CORRELATION_ID}`]) {
            throw new Error(`Invalid '${PF_HEADERS_PREFIX}${PFHeaders.CORRELATION_ID}' header.`);
        }

        if (!headers[`${PF_HEADERS_PREFIX}${PFHeaders.PROCESS_ID}`]) {
            throw new Error(`Invalid '${PF_HEADERS_PREFIX}${PFHeaders.CORRELATION_ID}' header.`);
        }

        if (headers[`${PF_HEADERS_PREFIX}${PFHeaders.PARENT_ID}`] === undefined) {
            throw new Error(`Invalid '${PF_HEADERS_PREFIX}${PFHeaders.PARENT_ID}' header.`);
        }

        if (!headers[`${PF_HEADERS_PREFIX}${PFHeaders.SEQUENCE_ID}`] ||
            parseInt(headers[`${PF_HEADERS_PREFIX}${PFHeaders.SEQUENCE_ID}`], 10) < 1) {
            throw new Error(`Invalid '${PF_HEADERS_PREFIX}${PFHeaders.SEQUENCE_ID}' header.`);
        }
    }

    /**
     *
     * @param headers
     * @return {boolean}
     */
    public static containsAllMandatory(headers: { [key: string]: string }): boolean {
        try {
            Headers.validateMandatoryHeaders(headers);
            return true;
        } catch (err) {
            return false;
        }
    }

    constructor(private headers?: { [key: string]: string }) {
        if (!headers) {
            this.headers = {};
        }
    }

    /**
     *
     * @param {string} key
     * @param value
     */
    public setHeader(key: string, value: string) {
        this.headers[key] = value;
    }

    /**
     *
     * @param {string} key
     * @param value
     */
    public setPFHeader(key: string, value: string) {
        this.headers[`${PF_HEADERS_PREFIX}${key}`] = value;
    }

    /**
     *
     * @param {string} key
     * @return {string}
     */
    public getHeader(key: string): string {
        return this.headers[`${PF_HEADERS_PREFIX}${key}`];
    }

    /**
     *
     * @param {string} key
     * @return {string}
     */
    public getPFHeader(key: string): string {
        return this.headers[`${PF_HEADERS_PREFIX}${key}`];
    }

    /**
     *
     * @return any
     */
    public getRaw(): any {
        return JSON.parse(JSON.stringify(this.headers));
    }

    /**
     *
     * @param {string} key
     */
    public removePFHeader(key: string) {
        delete this.headers[`${PF_HEADERS_PREFIX}key`];
    }

    /**
     *
     * @param {string} key
     */
    public removeHeader(key: string) {
        delete this.headers[key];
    }

}

export default Headers;
