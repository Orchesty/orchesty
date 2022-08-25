export default class DateParseError extends Error {

    public code: number;

    public constructor(message: string, code: number) {
        super(message);
        this.name = this.constructor.name;
        this.code = 1000 + code;
    }

}
