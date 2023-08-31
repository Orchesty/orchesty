import entryFactory from "bpmn-js-properties-panel/lib/factory/EntryFactory"
import cmdHelper from "bpmn-js-properties-panel/lib/helper/CmdHelper"
import { getBusinessObject } from "bpmn-js/lib/util/ModelUtil"

export default function (group, element, translate) {
  const allowedTypes = ["connector", "batch", "custom"]
  if (
    !allowedTypes.includes(element.pipesType) &&
    (!element.businessObject ||
      !allowedTypes.includes(element.businessObject.pipesType))
  ) {
    return
  }

  group.entries.push(
    entryFactory.validationAwareTextField(translate, {
      id: "rabbitPrefetch",
      label: "Prefetch",
      modelProperty: "rabbitPrefetch",
      validate: (element, values) => {
        if (element.type === "bpmn:Process") {
          return {}
        }

        if (values.rabbitPrefetch && !/\d+/.test(values.rabbitPrefetch)) {
          return { rabbitPrefetch: "Prefetch must be an integer." }
        }

        return {}
      },
      getProperty(element) {
        return getBusinessObject(element).rabbitPrefetch
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties)
      },
    })
  )

  group.entries.push(
    entryFactory.validationAwareTextField(translate, {
      id: "timeout",
      label: "Timeout",
      modelProperty: "timeout",
      validate: (element, values) => {
        if (element.type === "bpmn:Process") {
          return {}
        }

        if (values.timeout && !/\d+/.test(values.timeout)) {
          return { timeout: "Timeout must be an integer." }
        }

        return {}
      },
      getProperty(element) {
        return getBusinessObject(element).timeout
      },
      setProperty(element, properties) {
        return cmdHelper.updateProperties(element, properties)
      },
    })
  )
}
