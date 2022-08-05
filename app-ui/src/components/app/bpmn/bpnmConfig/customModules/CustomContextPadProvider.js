import inherits from 'inherits'
import ContextPadProvider from 'bpmn-js/lib/features/context-pad/ContextPadProvider'
import { bind } from 'lodash'
import { is } from 'bpmn-js/lib/util/ModelUtil'

export default function CustomContextPadProvider(
  config,
  injector,
  eventBus,
  contextPad,
  modeling,
  elementFactory,
  connect,
  create,
  popupMenu,
  canvas,
  rules,
  translate
) {
  ContextPadProvider.call(
    this,
    config,
    injector,
    eventBus,
    contextPad,
    modeling,
    elementFactory,
    connect,
    create,
    popupMenu,
    canvas,
    rules,
    translate
  )

  const cached = bind(this.getContextPadEntries, this)

  this.getContextPadEntries = function (element) {
    const actions = cached(element)

    if (
      (is(element, 'bpmn:Task') || is(element, 'bpmn:Event') || is(element, 'bpmn:Gateway')) &&
      element.businessObject.pipesType &&
      element.businessObject.pipesType !== ''
    ) {
      delete actions['append.append-task']
      delete actions['append.end-event']
      delete actions['append.gateway']
      delete actions['append.intermediate-event']
      delete actions.replace
    }

    return actions
  }
}

inherits(CustomContextPadProvider, ContextPadProvider)

CustomContextPadProvider.$inject = [
  'config',
  'injector',
  'eventBus',
  'contextPad',
  'modeling',
  'elementFactory',
  'connect',
  'create',
  'popupMenu',
  'canvas',
  'rules',
  'translate',
]
