import inherits from 'inherits';
import BaseRenderer from 'diagram-js/lib/draw/BaseRenderer';
import BpmnRenderer from 'bpmn-js/lib/draw/BpmnRenderer';
import { is } from 'bpmn-js/lib/util/ModelUtil';

/**
 * A renderer that knows how to render custom elements.
 */
function CustomRenderer(eventBus, styles, pathMap, canvas) {
  BaseRenderer.call(this, eventBus, 1500);

  this.bpmnRenderer = new BpmnRenderer(eventBus, styles, pathMap, canvas);

  this.canRender = function (element) {
    return is(element, 'bpmn:Event') || is(element, 'bpmn:Task');
  };

  this.drawShape = (parentGfx, element) => {
    const pipesType = element.pipesType || element.businessObject.pipesType;

    switch (pipesType) {
      case 'cron':
        const timerCircle = this.bpmnRenderer.handlers['bpmn:Event'](parentGfx, element);
        this.bpmnRenderer.handlers['bpmn:TimerEventDefinition'](parentGfx, element);
        return timerCircle;
      break;

      case 'webhook':
        const webhookCircle = this.bpmnRenderer.handlers['bpmn:Event'](parentGfx, element);
        this.bpmnRenderer.handlers['bpmn:SignalEventDefinition'](parentGfx, element);
        return webhookCircle;
      break;

      case 'connector': return this.bpmnRenderer.handlers['bpmn:ServiceTask'](parentGfx, element);
      case 'parser': return this.bpmnRenderer.handlers['bpmn:ScriptTask'](parentGfx, element);
      case 'mapper': return this.bpmnRenderer.handlers['bpmn:BusinessRuleTask'](parentGfx, element);
    }
  };
}

inherits(CustomRenderer, BaseRenderer);

module.exports = CustomRenderer;