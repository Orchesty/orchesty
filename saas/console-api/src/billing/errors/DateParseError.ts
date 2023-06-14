import { BaseError } from '../../base/errors/BaseError';

export default class DateParseError extends BaseError {

    public code: number;

    public constructor(message: string, code: number) {
        super(message);
        this.name = this.constructor.name;
        this.code = 1000 + code;
    }

}
