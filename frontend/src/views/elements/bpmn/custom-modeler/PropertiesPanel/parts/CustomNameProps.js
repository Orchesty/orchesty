import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import { is, getBusinessObject } from 'bpmn-js/lib/util/ModelUtil';
import apiGatewayServer from 'services/apiGatewayServer';

export default function (group, element, translate) {
  if (is(element, 'bpmn:Collaboration')) {
    return;
  }

  let modelProperty = 'name';

  if (is(element, 'bpmn:TextAnnotation')) {
    modelProperty = 'text';
  }

  const pipesType = (getBusinessObject(element).pipesType);
  const type = pipesType === 'custom' ? 'custom_node' : 'connector';
  const typeKey = `pipes-${type}-list`;

  if (['connector', 'batch_connector', 'custom'].includes(pipesType)) {
    apiGatewayServer(() => {}, 'GET', `/nodes/${type}/list_nodes`, null).then(response => {
      localStorage.setItem(typeKey, JSON.stringify(response));
    });

    const selectOptions = JSON.parse(localStorage.getItem(typeKey) || '[]');

    group.entries.push(entryFactory.selectBox({
      id: 'names',
      label: 'Names',
      selectOptions: selectOptions.map(item => ({ name: item, value: item })),
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
