import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import { is, getBusinessObject } from 'bpmn-js/lib/util/ModelUtil';

export default function (group, element, translate) {
  if (is(element, 'bpmn:Collaboration')) {
    return;
  }

  let modelProperty = 'name';

  if (is(element, 'bpmn:TextAnnotation')) {
    modelProperty = 'text';
  }

  group.entries.push(
    entryFactory.validationAwareTextField({
      id: 'name',
      label: 'Name',
      modelProperty: modelProperty,
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
      getProperty: function(element) {
        return getBusinessObject(element).name;
      },
      setProperty: function(element, properties) {

        element = element.labelTarget || element;

        return cmdHelper.updateProperties(element, properties);
      },
    })
  );
}