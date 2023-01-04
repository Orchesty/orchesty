import CommandInterceptor from "diagram-js/lib/command/CommandInterceptor"

import inherits from "inherits"
import { PIPES_TYPE_USER } from "@/components/app/bpmn/bpnmConfig/customModules/CustomPalette"

export default function DefaultNameBehavior(injector) {
  injector.invoke(CommandInterceptor, this)
  this.postExecuted(
    "elements.create",
    function (context) {
      context.elements.forEach(function (shape) {
        if (
          shape.pipesType === PIPES_TYPE_USER ||
          ["bpmn:Event", "bpmn:Gateway"].includes(shape.type)
        ) {
          shape.businessObject.name = shape.pipesType
        }
      })
    },
    true
  )
}

DefaultNameBehavior.$inject = ["injector"]

inherits(DefaultNameBehavior, CommandInterceptor)
