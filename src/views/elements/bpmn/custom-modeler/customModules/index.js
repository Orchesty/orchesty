module.exports = {
  __init__: [ 'customRenderer', 'paletteProvider', 'elementFactory' ],
  elementFactory: [ 'type', require('./CustomElementFactory') ],
  customRenderer: [ 'type', require('./CustomRenderer') ],
  paletteProvider: [ 'type', require('./CustomPalette') ],
};
