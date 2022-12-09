export default class MetadataSearchError extends Error {

    public constructor(message = '') {
        super(message !== '' ? message : 'Metadata for queried tenant not found!');
        this.name = this.constructor.name;
    }

}
