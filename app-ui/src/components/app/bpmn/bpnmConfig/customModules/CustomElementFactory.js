import { assign } from 'lodash'
import inherits from 'inherits'
import ElementFactory from 'bpmn-js/lib/features/modeling/ElementFactory'
import { DEFAULT_LABEL_SIZE } from 'bpmn-js/lib/util/LabelUtil'

/**
 * A custom factory that knows how to create BPMN _and_ custom elements.
 */
export default function CustomElementFactory(bpmnFactory, moddle, translate) {
  ElementFactory.call(this, bpmnFactory, moddle, translate)

  this.create = function (elementType, attrs) {
    if (elementType === 'label') {
      return this.baseCreate(elementType, assign({ type: 'label' }, DEFAULT_LABEL_SIZE, attrs))
    }
    const element = this.createBpmnElement(elementType, attrs)
    // Creating of new shape from palette
    if (attrs.createPipes && attrs.pipesType && attrs.pipesType !== '') {
      // Set field in panel
      element.businessObject.pipesType = attrs.pipesType

      // Generate default name for task
      if (!element.businessObject.name) {
        element.businessObject.name = attrs.pipesType.charAt(0).toUpperCase() + attrs.pipesType.slice(1)
      }
    }
    return element
  }
}

inherits(CustomElementFactory, ElementFactory)

CustomElementFactory.$inject = ['bpmnFactory', 'moddle', 'translate']
