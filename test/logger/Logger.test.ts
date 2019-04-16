import "mocha";

import logger from "../../src/logger/Logger";

describe("Logger", () => {
    it("should should contain all mandatory fields #unit", () => {
        logger.info("This should be logged.", { correlation_id: "123", node_id: "test_node" });
        logger.error("This error should be logged.", { error: new Error("Some Exception")});
    });
});
