import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import utils from 'bpmn-js-properties-panel/lib/Utils';
import { getBusinessObject } from 'bpmn-js/lib/util/ModelUtil';

export default function (group, element, translate) {
  group.entries.push(entryFactory.validationAwareTextField(translate,{
    id: 'id',
    label: translate('Id'),
    modelProperty: 'id',
    hidden: element => true,
    getProperty(element) {
      return getBusinessObject(element).id;
    },
    setProperty(element, properties) {
      element = element.labelTarget || element;

      return cmdHelper.updateProperties(element, properties);
    },
    validate(element, values) {
      const bo = getBusinessObject(element);
      const idError = utils.isIdValid(bo, values.id, translate);

      return idError ? { id: idError } : {};
    },
  }));
}
