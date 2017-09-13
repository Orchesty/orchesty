import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';

export default function (group, element, translate) {
  if (element.type !== 'bpmn:Task' && element.type !== 'bpmn:Event') {
    return;
  }

  group.entries.push(entryFactory.textField({
    id: 'pipesType',
    label: translate('Type'),
    modelProperty: 'pipesType',
    hidden: (element) => true
  }));
};