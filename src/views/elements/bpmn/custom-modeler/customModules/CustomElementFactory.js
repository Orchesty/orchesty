import assign from 'lodash/object/assign';
import inherits from 'inherits';
import ElementFactory from 'bpmn-js/lib/features/modeling/ElementFactory';
import LabelUtil from 'bpmn-js/lib/util/LabelUtil';


/**
 * A custom factory that knows how to create BPMN _and_ custom elements.
 */
function CustomElementFactory(bpmnFactory, moddle, translate) {
  ElementFactory.call(this, bpmnFactory, moddle, translate);

  /**
   * Create a diagram-js element with the given type (any of shape, connection, label).
   *
   * @param  {String} elementType
   * @param  {Object} attrs
   *
   * @return {djs.model.Base}
   */
  this.create = function (elementType, attrs) {
    if (elementType === 'label') {
      return this.baseCreate(elementType, assign({type: 'label'}, LabelUtil.DEFAULT_LABEL_SIZE, attrs));
    }

    let element = this.createBpmnElement(elementType, attrs);

    // Creating of new shape from palette
    if (attrs.createPipes && attrs.pipesType && attrs.pipesType !== '') {
      // Set field in panel
      element.businessObject.pipesType = attrs.pipesType;

      // Generate default name for task
      if (!element.businessObject.name) {
        element.businessObject.name = attrs.pipesType.charAt(0).toUpperCase() + attrs.pipesType.slice(1);
      }
    }

    return element;
  };
}

inherits(CustomElementFactory, ElementFactory);

module.exports = CustomElementFactory;