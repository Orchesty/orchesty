export default class TenantSearchError extends Error {
  constructor(message: string) {
    super(message);
    this.name = this.constructor.name;
  }
}
