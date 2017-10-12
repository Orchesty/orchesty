import { assert } from "chai";
import "mocha";

import * as shuffle from "shuffle-array";
import JobMessage from "../../src/message/JobMessage";
import Resequencer from "../../src/node/Resequencer";
import {INodeLabel} from "../../src/topology/Configurator";

describe("Resequencer", () => {
    it("orders messages with same job_id by their sequenceId", () => {
        const messages: JobMessage[] = [];

        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        for (let i = 1; i <= 10; i++) {
            messages.push(new JobMessage(node, "corrId", "procId", "parId", i, {}, new Buffer("")));
        }
        const resequencer = new Resequencer("nodeId");
        let output: JobMessage[] = [];

        shuffle(messages);
        messages.forEach((msg: JobMessage) => {
            output = output.concat(resequencer.getMessages(msg));
        });

        assert.lengthOf(output, messages.length);
        let j = 1;
        output.forEach((msg: JobMessage) => {
            assert.equal(j, msg.getSequenceId());
            j++;
        });
    });

    it("orders messages by their sequenceId when also mixed job_id", () => {
        const messages: JobMessage[] = [];

        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        for (let i = 1; i <= 2; i++) {
            for (let j = 1; j <= 10; j++) {
                messages.push(new JobMessage(node, `${i}`, `${i}`, "", j, {}, new Buffer("")));
            }
        }
        const resequencer = new Resequencer("nodeId");
        let output1: JobMessage[] = [];
        let output2: JobMessage[] = [];

        shuffle(messages);
        messages.forEach((msg: JobMessage) => {
            if (msg.getProcessId() === "1") {
                output1 = output1.concat(resequencer.getMessages(msg));
            } else {
                output2 = output2.concat(resequencer.getMessages(msg));
            }
        });

        assert.lengthOf(output1, messages.length / 2);
        assert.lengthOf(output2, messages.length / 2);

        let k = 1;
        output1.forEach((msg: JobMessage) => {
            assert.equal(k, msg.getSequenceId());
            k++;
        });

        let l = 1;
        output1.forEach((msg: JobMessage) => {
            assert.equal(l, msg.getSequenceId());
            l++;
        });
    });
});
