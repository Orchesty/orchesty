export default class PermissionsError extends Error {
  constructor(message = '') {
    super(message !== '' ? message : 'User doesnt have permissions for this operation!');
    this.name = this.constructor.name;
  }
}
