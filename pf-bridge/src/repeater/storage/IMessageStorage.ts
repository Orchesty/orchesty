
import {Message} from "amqplib";

interface IMessageStorage {

    /**
     * Saves message to storage
     *
     * @param {Message} message
     * @param {number} timeout
     * @return {Promise<boolean>}
     */
    save(message: Message, timeout: number): Promise<boolean>;

    /**
     * Returns messages that should be resent
     *
     * @return {Promise<Message[]>}
     */
    findExpired(): Promise<Message[]>;

}

export default IMessageStorage;
