
import {Message} from "amqplib";

interface IMessageStorage {

    /**
     * Saves message to storage
     *
     * @param {Message} message
     * @return {Promise<boolean>}
     */
    save(message: Message): Promise<boolean>;

    /**
     * Returns messages that should be resent
     *
     * @return {Promise<Message[]>}
     */
    findExpired(): Promise<Message[]>;

}

export default IMessageStorage;
