import { BaseError } from '../../base/errors/BaseError';

export default class MetadataSearchError extends BaseError {

    public constructor(message = '') {
        super(message !== '' ? message : 'Metadata for queried tenant not found!');
        this.name = this.constructor.name;
    }

}
