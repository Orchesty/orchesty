import entryFactory from "bpmn-js-properties-panel/lib/factory/EntryFactory"
import { is } from "bpmn-js/lib/util/ModelUtil"

export default function (group, element, translate) {
  if (
    !is(element, "bpmn:Task") &&
    !is(element, "bpmn:Event") &&
    !is(element, "bpmn:Gateway")
  ) {
    return
  }

  group.entries.push(
    entryFactory.textField(translate, {
      id: "pipesType",
      label: translate("Type"),
      modelProperty: "pipesType",
      // eslint-disable-next-line no-unused-vars
      hidden: (element) => true,
    })
  )
}
