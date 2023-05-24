import { container, initServices } from "../src";
import Mongo from "../src/storage/mongo/Mongo";
import Services from "../src/DIContainer/Services";
import TimeModule from "../src/TimeModule";

beforeAll(async () => {
    await initServices();

    jest.spyOn(TimeModule.prototype,'getNow').mockImplementation(() => 1680652800000);

    const endOfMonthDay = new Date(1680652800000);
    endOfMonthDay.setMonth(endOfMonthDay.getMonth() + 1);
    endOfMonthDay.setDate(1);
    endOfMonthDay.setHours(0, 0, 0, 0);

    jest.spyOn(TimeModule.prototype,'getEndOfMonthDay').mockImplementation(() => endOfMonthDay);
})

afterAll(async () => {
    await container.get<Mongo>(Services.MONGO).disconnect();
})

jest.setTimeout(30000);
