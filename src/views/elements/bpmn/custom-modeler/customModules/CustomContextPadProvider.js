import inherits from 'inherits';
import ContextPadProvider from 'bpmn-js/lib/features/context-pad/ContextPadProvider';
import bind from 'lodash/function/bind';
import { is } from 'bpmn-js/lib/util/ModelUtil';

function CustomContextPadProvider(eventBus, contextPad, modeling, elementFactory, connect,
                                  create, popupMenu, canvas, rules, translate) {

  ContextPadProvider.call(
    this, eventBus, contextPad, modeling, elementFactory, connect, create, popupMenu, canvas, rules, translate
  );

  let cached = bind(this.getContextPadEntries, this);

  this.getContextPadEntries = function (element) {
    let actions = cached(element);

    if (
      (is(element, 'bpmn:Task') || is(element, 'bpmn:Event')) &&
      element.businessObject.pipesType && element.businessObject.pipesType !== ''
    ) {
      delete actions["append.append-task"];
      delete actions["append.end-event"];
      delete actions["append.gateway"];
      delete actions["append.intermediate-event"];
      delete actions["replace"];
    }

    return actions;
  };
}

inherits(CustomContextPadProvider, ContextPadProvider);

CustomContextPadProvider.$inject = [
  'eventBus',
  'contextPad',
  'modeling',
  'elementFactory',
  'connect',
  'create',
  'popupMenu',
  'canvas',
  'rules',
  'translate'
];

module.exports = CustomContextPadProvider;