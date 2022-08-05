export default class MissingJWTError extends Error {
  constructor(message = '') {
    super(message !== '' ? message : 'JWT token is missing in request header!');
    this.name = this.constructor.name;
  }
}
