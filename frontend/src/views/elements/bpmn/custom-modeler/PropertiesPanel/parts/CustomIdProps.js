import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import utils from 'bpmn-js-properties-panel/lib/Utils';
import { getBusinessObject } from 'bpmn-js/lib/util/ModelUtil';

export default function(group, element, translate) {

  group.entries.push(entryFactory.validationAwareTextField({
    id: 'id',
    label: translate('Id'),
    modelProperty: 'id',
    hidden: (element) => true,
    getProperty: function(element) {
      return getBusinessObject(element).id;
    },
    setProperty: function(element, properties) {

      element = element.labelTarget || element;

      return cmdHelper.updateProperties(element, properties);
    },
    validate: function(element, values) {
      let bo = getBusinessObject(element);
      let idError = utils.isIdValid(bo, values.id);

      return idError ? { id: idError } : {};
    }
  }));

};
