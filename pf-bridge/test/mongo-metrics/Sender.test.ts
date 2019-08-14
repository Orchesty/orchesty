import {Sender} from "../../src/mongo-metrics/Sender";

describe("MongoDB Sender", () => {

    it("should send udp packet", (done) => {
        const sender = new Sender("127.0.0.127", 27017, "bridge");
        sender.send({field: "value"});

        done();
    });

});
