import inherits from 'inherits';
import BaseRenderer from 'diagram-js/lib/draw/BaseRenderer';
import BpmnRenderer from 'bpmn-js/lib/draw/BpmnRenderer';
import { is, getBusinessObject } from 'bpmn-js/lib/util/ModelUtil';
import svgAppend from 'tiny-svg/lib/append';
import svgAttr from 'tiny-svg/lib/attr';
import svgCreate from 'tiny-svg/lib/create';

/**
 * A renderer that knows how to render custom elements.
 */
function CustomRenderer(eventBus, styles, pathMap, canvas) {
  BaseRenderer.call(this, eventBus, 1500);

  this.bpmnRenderer = new BpmnRenderer(eventBus, styles, pathMap, canvas);
  this.computeStyle = styles.computeStyle;
  this.pathMap = pathMap;

  initPathMaps(this.pathMap.pathMap);

  this.canRender = function (element) {
    return is(element, 'bpmn:Event') || is(element, 'bpmn:Task') || is(element, 'bpmn:Gateway');
  };

  this.drawShape = (parentGfx, element) => {
    if (element.type === 'label') {
      return;
    }

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

        case 'signal':
            const signalCircle = this.bpmnRenderer.handlers['bpmn:Event'](parentGfx, element);
            this.bpmnRenderer.handlers['bpmn:EscalationEventDefinition'](parentGfx, element);
            return signalCircle;
            break;

        case 'splitter':
            let splitterTask = this.bpmnRenderer.handlers['bpmn:Task'](parentGfx, element);

            const splitterDathData = this.pathMap.getScaledPath('PIPES_SPLITTER', {
                abspos: {x: 0, y: 0}
            });

            this.drawPath(parentGfx, splitterDathData, {
                strokeWidth: 0.85,
                fill: 'black',
                stroke: 'white'
            });

            return splitterTask;
            break;

        case 'batch':
            let batchTask = this.bpmnRenderer.handlers['bpmn:Task'](parentGfx, element);

            const batchPathData = this.pathMap.getScaledPath('PIPES_BATCH', {
                abspos: {x: 0, y: 0}
            });

            this.drawPath(parentGfx, batchPathData, {
                strokeWidth: 0.1,
                fill: 'black',
                stroke: 'black'
            });

            return batchTask;
            break;

        case 'batch_connector':
            let batchConnectorTask = this.bpmnRenderer.handlers['bpmn:ServiceTask'](parentGfx, element);

            let batchConnectorPathData = this.pathMap.getScaledPath('PIPES_REPEATER', {
                abspos: {x: 0, y: 0}
            });

            this.drawPath(parentGfx, batchConnectorPathData, {
                strokeWidth: 0.1,
                fill: 'black',
                stroke: 'black'
            });

            return batchConnectorTask;
            break;

        case 'connector':
            return this.bpmnRenderer.handlers['bpmn:ServiceTask'](parentGfx, element);

        case 'custom':
            return this.bpmnRenderer.handlers['bpmn:Task'](parentGfx, element);

        case 'xml_parser':
        case 'table_parser':
            return this.bpmnRenderer.handlers['bpmn:ScriptTask'](parentGfx, element);
    }
  };

  this.drawPath = (parentGfx, d, attrs) => {

    attrs = this.computeStyle(attrs, ['no-fill'], {
      strokeWidth: 2,
      stroke: 'black'
    });

    let path = svgCreate('path');

    svgAttr(path, {d: d});
    svgAttr(path, attrs);

    svgAppend(parentGfx, path);

    return path;
  };

  function initPathMaps(pathMaps) {
    pathMaps.PIPES_SPLITTER = {
      d: 'm23.111568,18.38351c-1.303697,-2.03687 -3.540125,-2.933318 -4.995211,-2.002463c-0.12574,0.080699 -0.240846,0.172033 -0.347194,0.273376l-1.553301,-2.429105l3.586418,-5.629544c0.293394,-0.542373 0.401619,-1.187966 0.259613,-1.837312c-0.136375,-0.62057 -0.47919,-1.142299 -0.940238,-1.509511l-0.240221,-0.15264l-4.251403,6.64923l-4.251403,-6.64923l-0.240221,0.15264c-0.461048,0.367212 -0.803863,0.888941 -0.940238,1.509511c-0.142631,0.649346 -0.034407,1.294939 0.259613,1.837312l3.586418,5.629544l-1.553301,2.429105c-0.105722,-0.101343 -0.221453,-0.192677 -0.346568,-0.273376c-1.455086,-0.930855 -3.691514,-0.034407 -4.995211,2.002463s-1.180459,4.443455 0.274002,5.374309c1.455086,0.930855 3.691514,0.034407 4.995211,-2.002463l3.211699,-5.040878l3.211699,5.040878c1.303697,2.03687 3.540125,2.933318 4.995211,2.002463s1.577699,-3.337439 0.274002,-5.374309l0.000626,0zm-13.273436,2.360292c-0.586789,0.917092 -1.33998,1.363127 -1.848572,1.475104c0,0 0,0 0,0c-0.165777,0.036283 -0.396614,0.058804 -0.553633,-0.041913c-0.17391,-0.111352 -0.289641,-0.405372 -0.309034,-0.787598c-0.031279,-0.618693 0.188298,-1.34561 0.603679,-1.994957c0.586789,-0.917092 1.33998,-1.363127 1.848572,-1.475104c0.166403,-0.036283 0.39724,-0.058804 0.554259,0.041288c0.17391,0.111352 0.289641,0.405372 0.309034,0.787598c0.031904,0.618693 -0.188298,1.34561 -0.603679,1.994957l-0.000626,0.000626zm4.792525,-5.638302c-0.345317,0 -0.625574,-0.280257 -0.625574,-0.625574s0.280257,-0.625574 0.625574,-0.625574s0.625574,0.280257 0.625574,0.625574s-0.280257,0.625574 -0.625574,0.625574zm7.503764,6.28452c-0.019393,0.3816 -0.135124,0.676246 -0.309034,0.787598c-0.157019,0.100717 -0.387856,0.078197 -0.553633,0.041913c0,0 0,0 0,0c-0.509218,-0.111978 -1.261783,-0.558012 -1.848572,-1.475104c-0.415381,-0.648721 -0.635584,-1.376264 -0.603679,-1.994957c0.019393,-0.3816 0.135124,-0.676246 0.309034,-0.787598c0.157019,-0.100092 0.387856,-0.078197 0.554259,-0.041288c0.509218,0.111978 1.261783,0.558012 1.848572,1.475104c0.415381,0.648721 0.635584,1.376264 0.603679,1.994957l-0.000626,-0.000626z'
    };

    pathMaps.PIPES_BATCH = {
      d: 'm17.678,5.1225c-4.032,-0.032 -7.904,2.752 -9.056,6.624c-0.896,2.784 -0.384,5.984 1.344,8.32c-1.408,-0.256 -2.848,-0.544 -4.288,-0.832c-0.128,0.64 -0.256,1.28 -0.384,1.888c2.592,0.512 5.184,1.024 7.744,1.504c0.512,-2.56 1.024,-5.12 1.504,-7.68c-0.608,-0.128 -1.248,-0.256 -1.888,-0.352c-0.288,1.536 -0.608,3.104 -0.896,4.64c-2.208,-2.624 -2.176,-6.848 0.064,-9.44c2.208,-2.752 6.4,-3.552 9.44,-1.824c2.944,1.504 4.576,5.088 3.776,8.288c-0.704,3.328 -3.968,5.888 -7.36,5.824c-0.896,-0.064 -1.344,1.216 -0.608,1.696c0.64,0.416 1.44,0.128 2.144,0.096c3.968,-0.608 7.328,-4 7.84,-8c0.608,-3.808 -1.472,-7.808 -4.864,-9.6c-1.376,-0.736 -2.944,-1.152 -4.512,-1.152z'
    };

    pathMaps.PIPES_REPEATER = {
      d: 'm42.830498,3.510231c-3.922809,-0.031133 -7.689951,2.677473 -8.810754,6.444615c-0.871735,2.708606 -0.373601,5.821947 1.307603,8.094686c-1.36987,-0.249067 -2.770873,-0.529268 -4.171876,-0.809469c-0.124534,0.622668 -0.249067,1.245336 -0.373601,1.836871c2.521806,0.498135 5.043612,0.996269 7.534284,1.46327c0.498135,-2.490673 0.996269,-4.981345 1.46327,-7.472018c-0.591535,-0.124534 -1.214203,-0.249067 -1.836871,-0.342467c-0.280201,1.494404 -0.591535,3.01994 -0.871735,4.514344c-2.148205,-2.552939 -2.117072,-6.662549 0.062267,-9.184355c2.148205,-2.677473 6.226681,-3.455808 9.184355,-1.774604c2.864273,1.46327 4.452077,4.950212 3.673742,8.063552c-0.684935,3.237874 -3.860542,5.728547 -7.160684,5.66628c-0.871735,-0.062267 -1.307603,1.183069 -0.591535,1.650071c0.622668,0.404734 1.401003,0.124534 2.085938,0.0934c3.860542,-0.591535 7.12955,-3.891676 7.627685,-7.783352c0.591535,-3.704875 -1.432137,-7.596551 -4.732278,-9.340022c-1.338736,-0.716068 -2.864273,-1.120803 -4.38981,-1.120803z'
    };
  }
}

inherits(CustomRenderer, BaseRenderer);

module.exports = CustomRenderer;