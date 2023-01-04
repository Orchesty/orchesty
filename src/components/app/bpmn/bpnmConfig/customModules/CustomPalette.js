import { assign } from "lodash"
import { getDi } from "bpmn-js/lib/util/ModelUtil"

/**
 * A palette provider for BPMN 2.0 elements.
 */
export default function PaletteProvider(
  palette,
  create,
  elementFactory,
  spaceTool,
  lassoTool,
  handTool,
  globalConnect,
  translate
) {
  this._palette = palette
  this._create = create
  this._elementFactory = elementFactory
  this._spaceTool = spaceTool
  this._lassoTool = lassoTool
  this._handTool = handTool
  this._globalConnect = globalConnect
  this._translate = translate

  palette.registerProvider(this)
}

PaletteProvider.$inject = [
  "palette",
  "create",
  "elementFactory",
  "spaceTool",
  "lassoTool",
  "handTool",
  "globalConnect",
  "translate",
]

export const PIPES_TYPE_USER = "user"
export const PIPES_TYPE_CUSTOM = "custom"
export const PIPES_TYPE_GATEWAY = "gateway"
export const PIPES_TYPE_BATCH = "batch"
export const PIPES_TYPE_CONNECTOR = "connector"
export const PIPES_TYPE_START = "start"
export const PIPES_TYPE_WEBHOOK = "webhook"
export const PIPES_TYPE_CRON = "cron"

PaletteProvider.prototype.getPaletteEntries = function () {
  var actions = {},
    create = this._create,
    elementFactory = this._elementFactory,
    lassoTool = this._lassoTool,
    handTool = this._handTool,
    translate = this._translate

  function createAction(type, group, className, title, options) {
    function createListener(event) {
      var shape = elementFactory.createShape(assign({ type: type }, options))

      if (options) {
        var di = getDi(shape)
        di.isExpanded = options.isExpanded
      }

      create.start(event, shape)
    }

    var shortType = type.replace(/^bpmn:/, "")

    return {
      group: group,
      className: className,
      title: title || translate("Create {type}", { type: shortType }),
      action: {
        dragstart: createListener,
        click: createListener,
      },
    }
  }
  assign(actions, {
    "hand-tool": {
      group: "tools",
      className: "bpmn-icon-hand-tool",
      title: translate("Activate the hand tool"),
      action: {
        click: function (event) {
          handTool.activateHand(event)
        },
      },
    },
    "lasso-tool": {
      group: "tools",
      className: "bpmn-icon-lasso-tool",
      title: translate("Activate the lasso tool"),
      action: {
        click: function (event) {
          lassoTool.activateSelection(event)
        },
      },
    },
    "tool-separator": {
      group: "tools",
      separator: true,
    },
    "create.cron-event": createAction(
      "bpmn:Event",
      "events",
      "bpmn-icon-start-event-timer",
      "Cron",
      {
        pipesType: PIPES_TYPE_CRON,
      }
    ),
    "create.webhook-event": createAction(
      "bpmn:Event",
      "events",
      "bpmn-icon-start-event-signal",
      "Webhook",
      {
        pipesType: PIPES_TYPE_WEBHOOK,
      }
    ),
    "create.start-event": createAction(
      "bpmn:Event",
      "events",
      "bpmn-icon-start-event-none",
      "Start",
      {
        pipesType: PIPES_TYPE_START,
      }
    ),
    "event-separator": {
      group: "events",
      separator: true,
    },
    "create.connector": createAction(
      "bpmn:Task",
      "activity",
      "bpmn-icon-service-task",
      "Connector",
      {
        pipesType: PIPES_TYPE_CONNECTOR,
      }
    ),
    "create.batch": createAction(
      "bpmn:Task",
      "activity",
      "bpmn-icon-pipes-connector-batch",
      "Batch",
      {
        pipesType: PIPES_TYPE_BATCH,
      }
    ),
    "create.custom": createAction(
      "bpmn:Task",
      "activity",
      "bpmn-icon-task-none",
      "Custom",
      { pipesType: PIPES_TYPE_CUSTOM }
    ),
    "create.user": createAction(
      "bpmn:Task",
      "activity",
      "bpmn-icon-user",
      "User task",
      { pipesType: PIPES_TYPE_USER }
    ),
    "activity-separator": {
      group: "activity",
      separator: true,
    },
    "create.gateway": createAction(
      "bpmn:Gateway",
      "gateway",
      "bpmn-icon-gateway-none",
      "Gateway",
      {
        pipesType: PIPES_TYPE_GATEWAY,
      }
    ),
  })

  return actions
}
