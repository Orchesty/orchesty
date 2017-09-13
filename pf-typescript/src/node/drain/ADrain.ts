import JobMessage from "../../message/JobMessage";
import Resequencer from "./../Resequencer";
import IDrain from "./IDrain";

abstract class ADrain implements IDrain {

    protected resequencer: Resequencer;

    constructor(enableResequencer: boolean = false) {
        this.resequencer = enableResequencer ? new Resequencer() : null;
    }

    /**
     *
     * @param {JobMessage} message
     * @return {JobMessage[]}
     */
    public getMessageBuffer(message: JobMessage): JobMessage[] {
        return this.resequencer ? this.resequencer.getMessages(message) : [message];
    }

    public abstract forward(msgOut: JobMessage): Promise<JobMessage>;
}

export default ADrain;
