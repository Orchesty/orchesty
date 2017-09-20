import assign from 'lodash/object/assign';

function PaletteProvider(palette, create, elementFactory, spaceTool, lassoTool, handTool, globalConnect, translate) {

  this._palette = palette;
  this._create = create;
  this._elementFactory = elementFactory;
  this._spaceTool = spaceTool;
  this._lassoTool = lassoTool;
  this._handTool = handTool;
  this._globalConnect = globalConnect;
  this._translate = translate;

  palette.registerProvider(this);
}

module.exports = PaletteProvider;

PaletteProvider.$inject = [
  'palette',
  'create',
  'elementFactory',
  'spaceTool',
  'lassoTool',
  'handTool',
  'globalConnect',
  'translate'
];


PaletteProvider.prototype.getPaletteEntries = function() {

  let actions  = {};
  let create = this._create;
  let elementFactory = this._elementFactory;
  let spaceTool = this._spaceTool;
  let lassoTool = this._lassoTool;
  let handTool = this._handTool;
  let globalConnect = this._globalConnect;
  let translate = this._translate;

  function createAction(type, group, className, title, options) {

    function createListener(event) {
      let shape = elementFactory.createShape(assign({ type: type, createPipes: true }, options));

      if (options) {
        shape.businessObject.di.isExpanded = options.isExpanded;
      }

      create.start(event, shape);
    }

    let shortType = type.replace(/^bpmn\:/, '');

    return {
      group: group,
      className: className,
      title: title || translate('Create {type}', { type: shortType }),
      action: {
        dragstart: createListener,
        click: createListener
      }
    };
  }

  assign(actions, {
    'hand-tool': {
      group: 'tools',
      className: 'bpmn-icon-hand-tool',
      title: translate('Activate the hand tool'),
      action: {
        click: function(event) {
          handTool.activateHand(event);
        }
      }
    },
    'lasso-tool': {
      group: 'tools',
      className: 'bpmn-icon-lasso-tool',
      title: translate('Activate the lasso tool'),
      action: {
        click: function(event) {
          lassoTool.activateSelection(event);
        }
      }
    },
    'space-tool': {
      group: 'tools',
      className: 'bpmn-icon-space-tool',
      title: translate('Activate the create/remove space tool'),
      action: {
        click: function(event) {
          spaceTool.activateSelection(event);
        }
      }
    },
    'global-connect-tool': {
      group: 'tools',
      className: 'bpmn-icon-connection-multi',
      title: translate('Activate the global connect tool'),
      action: {
        click: function(event) {
          globalConnect.toggle(event);
        }
      }
    },
    'tool-separator': {
      group: 'tools',
      separator: true
    },
    'create.start-event': createAction(
      'bpmn:Event', 'events', 'bpmn-icon-start-event-timer', 'Cron', { pipesType: 'cron'}
    ),
    'create.signal-event': createAction(
      'bpmn:Event', 'events', 'bpmn-icon-start-event-signal', 'Webhook', { pipesType: 'webhook'}
    ),
    'event-separator': {
      group: 'events',
      separator: true
    },
    'create.generic': createAction(
      'bpmn:Task', 'activity', 'bpmn-icon-task-none', 'Generic', { pipesType: 'generic'}
    ),
    'create.connector': createAction(
      'bpmn:Task', 'activity', 'bpmn-icon-service-task', 'Connector', { pipesType: 'connector'}
    ),
    'create.parser': createAction(
      'bpmn:Task', 'activity', 'bpmn-icon-script-task', 'Parser', { pipesType: 'parser'}
    ),
    'create.batch': createAction(
      'bpmn:Task', 'activity', 'bpmn-icon-pipes-batch', 'Batch', { pipesType: 'batch'}
    ),
    'create.splitter': createAction(
      'bpmn:Task', 'activity', 'bpmn-icon-pipes-splitter', 'Splitter', { pipesType: 'splitter'}
    ),
    'task-separator': {
      group: 'activity',
      separator: true
    },
    'create.exclusive-gateway': createAction(
      'bpmn:ExclusiveGateway', 'gateway', 'bpmn-icon-gateway-xor', 'Gateway', { pipesType: 'gateway'}
    ),
  });

  return actions;
};
