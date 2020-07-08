import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import { getBusinessObject, is } from 'bpmn-js/lib/util/ModelUtil';
import apiGatewayServer from 'services/apiGatewayServer';

export default function (group, element, translate) {
  if (is(element, 'bpmn:Collaboration')) {
    return;
  }

  let modelProperty = 'name';

  if (is(element, 'bpmn:TextAnnotation')) {
    modelProperty = 'text';
  }

  apiGatewayServer(() => {}, 'GET', '/nodes/list/name', null).then(response => {
    localStorage.setItem('pipes-nodes-list', JSON.stringify(response));
  });

  const pipesType = getBusinessObject(element).pipesType;
  const pipesNodes = JSON.parse(localStorage.getItem('pipes-nodes-list') || '[]');
  const implementationTypes = Object.keys(pipesNodes);
  const implementationTypesNames = JSON.parse(localStorage.getItem('pipes')).node.implementations;

  if (element.type !== 'bpmn:Process') {
    group.entries.push(entryFactory.selectBox({
      id: 'sdkHost',
      label: 'Services',
      selectOptions: implementationTypesNames.map(({ key, value }) => ({ name: value, value: key })),
      modelProperty: 'sdkHost',
      getProperty(element) {
        return getBusinessObject(element).sdkHost;
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties);
      },
    }));
  }

  if (['connector', 'batch_connector', 'custom', 'user'].includes(pipesType)) {
    const sdkHost = document.getElementById('camunda-sdkHost-select');
    const serviceName = document.getElementById('camunda-name-select');
    const nodeType = pipesType === 'batch_connector' ? 'connector' : pipesType;
    let sdkHostValue = implementationTypes[0];
    if (sdkHost && serviceName) {
      sdkHostValue = sdkHost.options[sdkHost.selectedIndex].text;

      const length = serviceName.options.length;
      for (let i = 0; i < length; i++) {
        serviceName.options.remove(0);
      }

      pipesNodes[sdkHostValue][nodeType].map(item => {
        serviceName.options.add(new Option(item, item));
      });
    }

    if(!sdkHostValue || pipesNodes.length === 0) {
      return {};
    }

    group.entries.push(entryFactory.selectBox({
      id: 'name',
      label: 'Name',
      selectOptions: pipesNodes[sdkHostValue][nodeType].map(item => ({ name: item, value: item })),
      modelProperty,
      validate: (element, values) => {
        if (element.type === 'bpmn:Process') {
          return {};
        }

        if (!values.name || values.name === 'Custom') {
          return { name: 'Name must not be empty.' };
        }

        return {};
      },
      getProperty(element) {
        return getBusinessObject(element).name;
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties);
      },
    }));
  } else {
    group.entries.push(entryFactory.validationAwareTextField({
      id: 'name',
      label: 'Name',
      modelProperty,
      validate: (element, values) => {
        if (element.type === 'bpmn:Process') {
          return {};
        }

        if (/\s/.test(values.name)) {
          return { name: 'Name must not contain spaces.' };
        }

        if (!values.name) {
          return { name: 'Name must not be empty.' };
        }

        return {};
      },
      getProperty(element) {
        return getBusinessObject(element).name;
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties);
      },
    }));
  }
}
