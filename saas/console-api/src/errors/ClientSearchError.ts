export default class ClientSearchError extends Error {

    public constructor(message: string) {
        super(message);
        this.name = this.constructor.name;
    }

}
