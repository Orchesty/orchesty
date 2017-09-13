import inherits from 'inherits';
import PropertiesActivator from 'bpmn-js-properties-panel/lib/PropertiesActivator';
import processProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/ProcessProps';
import eventProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/EventProps';
import linkProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/LinkProps';
import documentationProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/DocumentationProps';
import idProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/IdProps';
import nameProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/NameProps';
import executableProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/ExecutableProps';
import ElementPipesTypeProps from './parts/PipesTypeProps';

function createGeneralTabGroups(element, bpmnFactory, elementRegistry, translate) {
  const generalGroup = {
    id: 'general',
    label: translate('General'),
    entries: [],
  };

  idProps(generalGroup, element, translate);
  nameProps(generalGroup, element, translate);
  processProps(generalGroup, element, translate);
  ElementPipesTypeProps(generalGroup, element, translate);
  executableProps(generalGroup, element, translate);

  const detailsGroup = {
    id: 'details',
    label: translate('Details'),
    entries: [],
  };

  linkProps(detailsGroup, element, translate);
  eventProps(detailsGroup, element, bpmnFactory, elementRegistry, translate);

  const documentationGroup = {
    id: 'documentation',
    label: translate('Documentation'),
    entries: [],
  };

  documentationProps(documentationGroup, element, bpmnFactory, translate);

  return [
    generalGroup,
    detailsGroup,
    documentationGroup,
  ];
}

function PipesPropertiesProvider(eventBus, bpmnFactory, elementRegistry, translate) {
  PropertiesActivator.call(this, eventBus);

  this.getTabs = function (element) {
    const generalTab = {
      id: 'general',
      label: translate('General'),
      groups: createGeneralTabGroups(element, bpmnFactory, elementRegistry, translate),
    };

    return [
      generalTab,
    ];
  };
}

PipesPropertiesProvider.$inject = [ 'eventBus', 'bpmnFactory', 'elementRegistry', 'translate' ];

inherits(PipesPropertiesProvider, PropertiesActivator);

module.exports = PipesPropertiesProvider;
