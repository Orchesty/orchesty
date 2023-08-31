import {StringUtils} from "hb-utils/dist/lib/StringUtils";
import {TimeUtils} from "hb-utils/dist/lib/TimeUtils";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import {Sender} from "./Sender";

const RESERVED_CHARS = [",", "=", " "];
const SERVER = "mongodb";
const PORT = 27017;

export interface ITagsMap { [key: string]: string; }

/**
 * Use instance of this class for sending metrics to MongoDB
 */
export class MongoMetrics implements IMetrics {

    private measurement: string;
    private tags: ITagsMap;
    private sender: Sender;

    /**
     *
     * @param {string} measurement
     * @param {ITagsMap} tags
     * @param {string} server optional
     * @param {string} port optional
     */
    public constructor(measurement: string, tags: ITagsMap = {}, server?: string, port?: number) {
        this.measurement = StringUtils.escapeChars(measurement, RESERVED_CHARS);
        this.tags = tags;

        this.sender = new Sender(server || SERVER, port || PORT, measurement);
    }

    /**
     * Adds new tag
     *
     * @param {string} key
     * @param {string} value
     */
    public addTag(key: string, value: string) {
        this.tags[key] = value;
    }

    /**
     * Removes tag
     *
     * @param {string} key
     */
    public removeTag(key: string) {
        delete this.tags[key];
    }

    /**
     * Sends document
     *
     * @param fieldSet
     * @param includeTime
     * @return {Promise}
     */
    public send(fieldSet: {}, includeTime = true): Promise<string> {
        if (includeTime) {
            fieldSet = {
                ...fieldSet,
                created: TimeUtils.nowSeconds(),
            };
        }

        return this.sender.send({
            fields: fieldSet,
            tags: this.tags,
        });
    }

}
