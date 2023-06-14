import { BaseError } from './BaseError';

export default class PermissionsError extends BaseError {

    public constructor(message = '') {
        super(message !== '' ? message : 'User doesnt have permissions for this operation!');
        this.name = this.constructor.name;
    }

}
