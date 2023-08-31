import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory'
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper'
import { getBusinessObject } from 'bpmn-js/lib/util/ModelUtil'

export default function (group, element, translate) {
  const allowedTypes = ['connector', 'batch']
  if (
    !allowedTypes.includes(element.pipesType) &&
    (!element.businessObject || !allowedTypes.includes(element.businessObject.pipesType))
  ) {
    return
  }

  group.entries.push(
    entryFactory.validationAwareTextField(translate, {
      id: 'limiterInterval',
      label: 'Limit (in secs)',
      modelProperty: 'limiterInterval',
      validate: (element, values) => {
        if (element.type === 'bpmn:Process') {
          return {}
        }

        if (!values.limiterInterval) {
          return { limiterInterval: 'Limit must be specified' }
        }

        if (values.limiterInterval && !/\d+/.test(values.limiterInterval)) {
          return { limiterInterval: 'Limiter interval must be an integer.' }
        }

        return {}
      },
      getProperty(element) {
        return getBusinessObject(element).limiterInterval
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties)
      },
    })
  )
}
