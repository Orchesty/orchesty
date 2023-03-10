import inherits from "inherits"
import PropertiesActivator from "bpmn-js-properties-panel/lib/PropertiesActivator"
import processProps from "bpmn-js-properties-panel/lib/provider/bpmn/parts/ProcessProps"
import eventProps from "bpmn-js-properties-panel/lib/provider/bpmn/parts/EventProps"
import linkProps from "bpmn-js-properties-panel/lib/provider/bpmn/parts/LinkProps"
import CustomIdProps from "./parts/CustomIdProps"
import CustomNameProps from "./parts/CustomNameProps"
import ElementPipesTypeProps from "./parts/PipesTypeProps"
import ConnectorRepeaterEnabled from "./parts/ConnectorRepeaterEnabled"
import ConnectorRepeaterHops from "./parts/ConnectorRepeaterHops"
import ConnectorRepeaterInterval from "./parts/ConnectorRepeaterInterval"
import PrefetchProps from "./parts/PrefetchProps.js"
import { is } from "bpmn-js/lib/util/ModelUtil"

function createGeneralTabGroups(
  element,
  bpmnFactory,
  elementRegistry,
  translate
) {
  if (is(element, "bpmn:SequenceFlow")) {
    return
  }
  const generalGroup = {
    id: "node",
    label: translate("Node"),
    entries: [],
  }

  CustomIdProps(generalGroup, element, translate)
  CustomNameProps(generalGroup, element, translate)
  processProps(generalGroup, element, translate)
  ElementPipesTypeProps(generalGroup, element, translate)

  const detailsGroup = {
    id: "details",
    label: translate("Details"),
    entries: [],
  }

  linkProps(detailsGroup, element, translate)
  eventProps(detailsGroup, element, bpmnFactory, elementRegistry, translate)

  const userTaskGroup = {
    id: "userTaskGroup",
    label: translate("User Task"),
    entries: [],
  }

  return [generalGroup, detailsGroup, userTaskGroup]
}

function createRepeaterTabGroups(
  element,
  bpmnFactory,
  elementRegistry,
  translate
) {
  const repeaterGroup = {
    id: "repeater",
    label: translate("Repeater"),
    entries: [],
  }

  ConnectorRepeaterEnabled(repeaterGroup, element, translate)
  ConnectorRepeaterHops(repeaterGroup, element, translate)
  ConnectorRepeaterInterval(repeaterGroup, element, translate)

  return [repeaterGroup]
}

function createLimiterTabGroups(
  element,
  bpmnFactory,
  elementRegistry,
  translate
) {
  const limiterGroup = {
    id: "limiter",
    label: translate("Limiter"),
    entries: [],
  }

  // ConnectorLimiterValue(limiterGroup, element, translate)
  // ConnectorLimiterInterval(limiterGroup, element, translate)

  return [limiterGroup]
}

function createBridgeTabGroups(
  element,
  bpmnFactory,
  elementRegistry,
  translate
) {
  const bridgeGroup = {
    id: "bridge",
    label: translate("Bridge"),
    entries: [],
  }

  PrefetchProps(bridgeGroup, element, translate)

  return [bridgeGroup]
}

export default function PipesPropertiesProvider(
  eventBus,
  bpmnFactory,
  elementRegistry,
  translate
) {
  PropertiesActivator.call(this, eventBus)

  this.getTabs = function (element) {
    return [
      {
        id: "node",
        label: translate("Node"),
        groups: createGeneralTabGroups(
          element,
          bpmnFactory,
          elementRegistry,
          translate
        ),
      },
      {
        id: "bridge",
        label: translate("Bridge"),
        groups: createBridgeTabGroups(
          element,
          bpmnFactory,
          elementRegistry,
          translate
        ),
      },
      {
        id: "repeater",
        label: translate("Repeater"),
        groups: createRepeaterTabGroups(
          element,
          bpmnFactory,
          elementRegistry,
          translate
        ),
      },
      {
        id: "limiter",
        label: translate("Limiter"),
        groups: createLimiterTabGroups(
          element,
          bpmnFactory,
          elementRegistry,
          translate
        ),
      },
    ]
  }
}

PipesPropertiesProvider.$inject = [
  "eventBus",
  "bpmnFactory",
  "elementRegistry",
  "translate",
]

inherits(PipesPropertiesProvider, PropertiesActivator)
