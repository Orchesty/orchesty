export default class TenantSearchError extends Error {

    public constructor(message: string) {
        super(message);
        this.name = this.constructor.name;
    }

}
