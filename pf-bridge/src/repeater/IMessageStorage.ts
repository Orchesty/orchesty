
import {Message} from "amqplib";

interface IMessageStorage {

    save(message: Message): Promise<boolean>;

    get(): Promise<Message[]>;

}

export default IMessageStorage;
