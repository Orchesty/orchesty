// get bpmn-js
const BpmnViewer = require("bpmn-js/lib/Modeler")

// load additional modules
const additionalModules = [
  require("bpmn-js-properties-panel"),
  require("bpmn-js-properties-panel/lib/provider/bpmn"),
]

// add additional (default!) modules to bpmn-js
BpmnViewer.prototype._modules =
  BpmnViewer.prototype._modules.concat(additionalModules)

// export
module.exports = BpmnViewer
