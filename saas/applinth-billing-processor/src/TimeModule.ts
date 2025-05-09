export default class TimeModule {

    private readonly now: number;

    private readonly endOfMonthDay: Date;

    public constructor() {
        const date = new Date();
        date.setHours(0, 0, 0, 0);

        this.now = date.getTime();

        this.endOfMonthDay = new Date();
        this.endOfMonthDay.setMonth(this.endOfMonthDay.getMonth() + 1);
        this.endOfMonthDay.setDate(1);
        this.endOfMonthDay.setHours(0, 0, 0, 0);
    }

    public getNow(): number {
        return this.now;
    }

    public getEndOfMonthDay(): Date {
        return this.endOfMonthDay;
    }

}
