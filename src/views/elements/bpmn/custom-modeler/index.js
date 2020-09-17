import Modeler from 'bpmn-js/lib/Modeler';
import inherits from 'inherits';

import PropertiesPanelModule from 'bpmn-js-properties-panel';
import PropertiesProviderModule from './PropertiesPanel/PropertiesProvider';
import PipesModdleDescriptor from './descriptors/pipes.json';
import CustomContextPadProvider from "./customModules/CustomContextPadProvider";
import CustomElementFactory from "./customModules/CustomElementFactory";
import CustomRenderer from "./customModules/CustomRenderer";
import CustomPalette from "./customModules/CustomPalette";

export default function CustomModeler(options) {
  options.additionalModules = [
    ...options.additionalModules || [],
    PropertiesPanelModule,
    {__init__: ['propertiesProvider', 'customRenderer', 'contextPadProvider', 'elementFactory', 'paletteProvider']},
    {propertiesProvider: ['type', PropertiesProviderModule]},
    {customRenderer: ['type', CustomRenderer]},
    {contextPadProvider: ['type', CustomContextPadProvider]},
    {elementFactory: ['type', CustomElementFactory]},
    {paletteProvider: ['type', CustomPalette]},
  ];

  options.moddleExtensions = {
    pipes: PipesModdleDescriptor,
  };

  Modeler.call(this, options);
}

inherits(CustomModeler, Modeler);