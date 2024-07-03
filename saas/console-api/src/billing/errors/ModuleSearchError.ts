import { BaseError } from '../../base/errors/BaseError';

export default class ModuleSearchError extends BaseError {

    public constructor(message = '') {
        super(message !== '' ? message : 'Module for queried tenant not found!');
        this.name = this.constructor.name;
    }

}
