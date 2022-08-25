export default class GranularityError extends Error {

    public code: number;

    public constructor(message = '') {
        super(message !== '' ? message : 'Granularity is not supported!');
        this.name = this.constructor.name;
        this.code = 2000;
    }

}
