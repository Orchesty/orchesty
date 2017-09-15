import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import { is } from 'bpmn-js/lib/util/ModelUtil';

export default function (group, element, translate) {
  if (!is(element, 'bpmn:Task') && !is(element, 'bpmn:Event')) {
    return;
  }

  group.entries.push(
    entryFactory.textField({
      id: 'pipesType',
      label: translate('Type'),
      modelProperty: 'pipesType',
      hidden: (element) => true
    })
  );
};