module.exports = {
  __init__: [ 'customRenderer', 'paletteProvider', 'elementFactory' ],
  contextPadProvider: [ 'type', require('./CustomContextPadProvider') ],
  elementFactory: [ 'type', require('./CustomElementFactory') ],
  customRenderer: [ 'type', require('./CustomRenderer') ],
  paletteProvider: [ 'type', require('./CustomPalette') ],
};
