import entryFactory from "bpmn-js-properties-panel/lib/factory/EntryFactory"
import cmdHelper from "bpmn-js-properties-panel/lib/helper/CmdHelper"
import { getBusinessObject } from "bpmn-js/lib/util/ModelUtil"

export default function (group, element, translate) {
  const allowedTypes = ["connector", "batch"]
  if (
    !allowedTypes.includes(element.pipesType) &&
    (!element.businessObject ||
      !allowedTypes.includes(element.businessObject.pipesType))
  ) {
    return
  }

  group.entries.push(
    entryFactory.validationAwareTextField(translate, {
      id: "limiterValue",
      label: "Key",
      modelProperty: "limiterValue",
      validate: (element, values) => {
        if (element.type === "bpmn:Process") {
          return {}
        }

        if (!values.limiterValue) {
          return { limiterValue: "Key must be specified" }
        }

        return {}
      },
      getProperty(element) {
        return getBusinessObject(element).limiterValue
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties)
      },
    })
  )
}
