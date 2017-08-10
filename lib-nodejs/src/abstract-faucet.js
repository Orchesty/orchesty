
class AbstractFaucet {

  constructor() {
    if (this.constructor === AbstractFaucet) {
      throw new TypeError('Abstract class "AbstractFaucet" cannot be instantiated directly.');
    }

    if (this.open === undefined) {
      throw new TypeError('Classes extending the "AbstractFaucet" abstract class must implement "open" method');
    }
  }
}

module.exports = AbstractFaucet;
