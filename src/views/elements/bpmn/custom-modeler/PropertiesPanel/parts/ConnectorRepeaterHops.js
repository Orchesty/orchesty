import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import {getBusinessObject} from 'bpmn-js/lib/util/ModelUtil';

export default function (group, element, translate) {
  const allowedTypes = ['connector', 'batch_connector'];
  if (!allowedTypes.includes(element.pipesType) && (!element.businessObject || !allowedTypes.includes(element.businessObject.pipesType))) {
    return;
  }

  group.entries.push(entryFactory.validationAwareTextField(translate, {
    id: 'repeaterHops',
    label: 'Repeater hops',
    modelProperty: 'repeaterHops',
    validate: (element, values) => {
      if (element.type === 'bpmn:Process') {
        return {};
      }

      if (values.repeaterHops && !/\d+/.test(values.repeaterHops)) {
        return {repeaterHops: 'Repeater hops must be an integer.'};
      }

      return {};
    },
    getProperty(element) {
      return getBusinessObject(element).repeaterHops;
    },
    setProperty(element, properties) {
      return cmdHelper.updateProperties(element, properties);
    },
  }));
}
