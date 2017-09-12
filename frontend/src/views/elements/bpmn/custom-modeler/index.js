import Modeler from 'bpmn-js/lib/Modeler';
import inherits from 'inherits';

import PropertiesPanelModule from 'bpmn-js-properties-panel';
import PropertiesProviderModule from './PropertiesPanel';
import PipesModdleDescriptor from './descriptors/pipes.json';

function CustomModeler(options) {
  options.additionalModules = [
    ...options.additionalModules || [],
    PropertiesPanelModule,
    PropertiesProviderModule,
  ];

  options.moddleExtensions = {
    pipes: PipesModdleDescriptor
  };

  Modeler.call(this, options);
}

inherits(CustomModeler, Modeler);

CustomModeler.prototype._modules = [].concat(
  CustomModeler.prototype._modules,
  [
    require('./customModules')
  ]
);

module.exports = CustomModeler;