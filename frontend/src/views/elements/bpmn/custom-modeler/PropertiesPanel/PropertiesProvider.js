import inherits from 'inherits';
import PropertiesActivator from 'bpmn-js-properties-panel/lib/PropertiesActivator';
import processProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/ProcessProps';
import eventProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/EventProps';
import linkProps from 'bpmn-js-properties-panel/lib/provider/bpmn/parts/LinkProps';
import CustomIdProps from './parts/CustomIdProps';
import CustomNameProps from './parts/CustomNameProps';
import ElementPipesTypeProps from './parts/PipesTypeProps';
import CronTimeProps from './parts/CronTimeProps.js';
import CronParamsProps from './parts/CronParamsProps.js';
import ConnectorRepeaterEnabled from './parts/ConnectorRepeaterEnabled';
import ConnectorRepeaterHops from './parts/ConnectorRepeaterHops';
import ConnectorRepeaterInterval from './parts/ConnectorRepeaterInterval';
import PrefetchProps from './parts/PrefetchProps.js';

function createGeneralTabGroups(element, bpmnFactory, elementRegistry, translate) {
  const generalGroup = {
    id: 'general',
    label: translate('General'),
    entries: [],
  };

  CustomIdProps(generalGroup, element, translate);
  CustomNameProps(generalGroup, element, translate);
  processProps(generalGroup, element, translate);
  ElementPipesTypeProps(generalGroup, element, translate);
  PrefetchProps(generalGroup, element, translate);

  const cronGroup = {
    id: 'cron',
    label: translate('Cron'),
    entries: [],
  };

  CronTimeProps(cronGroup, element, translate);
  CronParamsProps(cronGroup, element, translate);

  const connectorGroup = {
    id: 'connector',
    label: translate('Connector'),
    entries: [],
  };

  ConnectorRepeaterEnabled(connectorGroup, element, translate);
  ConnectorRepeaterHops(connectorGroup, element, translate);
  ConnectorRepeaterInterval(connectorGroup, element, translate);

  const detailsGroup = {
    id: 'details',
    label: translate('Details'),
    entries: [],
  };

  linkProps(detailsGroup, element, translate);
  eventProps(detailsGroup, element, bpmnFactory, elementRegistry, translate);

  return [
    generalGroup,
    detailsGroup,
    cronGroup,
    connectorGroup,
  ];
}

export default function PipesPropertiesProvider(eventBus, bpmnFactory, elementRegistry, translate) {
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

PipesPropertiesProvider.$inject = ['eventBus', 'bpmnFactory', 'elementRegistry', 'translate'];

inherits(PipesPropertiesProvider, PropertiesActivator);