import entryFactory from "bpmn-js-properties-panel/lib/factory/EntryFactory"
import cmdHelper from "bpmn-js-properties-panel/lib/helper/CmdHelper"
import { getBusinessObject } from "bpmn-js/lib/util/ModelUtil"

export default function (group, element, translate) {
  if (
    element.pipesType !== "cron" &&
    (!element.businessObject || element.businessObject.pipesType !== "cron")
  ) {
    return
  }

  group.entries.push(
    entryFactory.validationAwareTextField(translate, {
      id: "cronParams",
      label: "Cron parameters",
      description: 'JSON format without {}. Eg.: "key": "val", "foo": "bar"',
      modelProperty: "cronParams",
      validate: (element, values) => {
        if (!values.cronParams) return {}

        try {
          JSON.parse(`{${values.cronParams}}`)
        } catch (err) {
          return {
            cronParams: err.message
              .replace("undefinedundefinedundefined", "''")
              .replace("undefined", "unexpected"),
          }
        }

        return {}
      },
      getProperty(element) {
        let text = getBusinessObject(element).get("cronParams")

        if (!text) text = ""

        return decodeURIComponent(`${text}`.replace(/\+/g, "%20"))
      },
      setProperty(element, properties) {
        if (properties.cronParams) {
          properties.cronParams = encodeURIComponent(properties.cronParams)
            .replace(/!/g, "%21")
            .replace(/'/g, "%27")
            .replace(/\(/g, "%28")
            .replace(/\)/g, "%29")
            .replace(/\*/g, "%2A")
            .replace(/%20/g, "+")
        }

        return cmdHelper.updateProperties(element, properties)
      },
    })
  )
}
