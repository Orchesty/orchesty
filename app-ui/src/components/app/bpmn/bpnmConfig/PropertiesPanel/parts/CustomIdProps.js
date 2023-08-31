import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory'
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper'
import utils from 'bpmn-js-properties-panel/lib/Utils'
import { getBusinessObject, is } from 'bpmn-js/lib/util/ModelUtil'

export default function (group, element, translate) {
  var businessObject = getBusinessObject(element)
  if (is(element, 'bpmn:Process') || (is(element, 'bpmn:Participant') && businessObject.get('processRef'))) {
    return
  }
  group.entries.push(
    entryFactory.validationAwareTextField(translate, {
      id: 'id',
      label: translate('Id'),
      modelProperty: 'id',
      // eslint-disable-next-line no-unused-vars
      hidden: (element) => true,
      getProperty(element) {
        return getBusinessObject(element).id
      },
      setProperty(element, properties) {
        element = element.labelTarget || element

        return cmdHelper.updateProperties(element, properties)
      },
      validate(element, values) {
        const bo = getBusinessObject(element)
        const idError = utils.isIdValid(bo, values.id, translate)

        return idError ? { id: idError } : {}
      },
    })
  )
}
