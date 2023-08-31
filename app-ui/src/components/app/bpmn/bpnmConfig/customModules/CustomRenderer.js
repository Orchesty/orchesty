import inherits from "inherits"
import BaseRenderer from "diagram-js/lib/draw/BaseRenderer"
import BpmnRenderer from "bpmn-js/lib/draw/BpmnRenderer"
import { is } from "bpmn-js/lib/util/ModelUtil"
import { append, attr, create } from "tiny-svg"

/**
 * A renderer that knows how to render custom elements.
 */
export default function CustomRenderer(
  config,
  eventBus,
  styles,
  pathMap,
  canvas,
  textRenderer
) {
  BaseRenderer.call(this, eventBus, 1500)

  this.bpmnRenderer = new BpmnRenderer(
    config,
    eventBus,
    styles,
    pathMap,
    canvas,
    textRenderer,
    500
  )
  this.computeStyle = styles.computeStyle
  this.pathMap = pathMap

  initPathMaps(this.pathMap.pathMap)

  this.canRender = function (element) {
    return (
      is(element, "bpmn:Event") ||
      is(element, "bpmn:Task") ||
      is(element, "bpmn:Gateway")
    )
  }

  this.drawShape = (parentGfx, element) => {
    if (element.type === "label") {
      return
    }

    const pipesType = element.pipesType || element.businessObject.pipesType

    switch (pipesType) {
      case "cron":
        const timerCircle = this.bpmnRenderer.handlers["bpmn:Event"](
          parentGfx,
          element,
          {}
        )
        this.bpmnRenderer.handlers["bpmn:TimerEventDefinition"](
          parentGfx,
          element
        )
        return timerCircle

      case "start":
        const start = this.bpmnRenderer.handlers["bpmn:Event"](
          parentGfx,
          element,
          {}
        )
        this.bpmnRenderer.handlers["bpmn:StartEvent"](parentGfx, element)
        return start

      case "webhook":
        const webhookCircle = this.bpmnRenderer.handlers["bpmn:Event"](
          parentGfx,
          element,
          {}
        )
        this.bpmnRenderer.handlers["bpmn:SignalEventDefinition"](
          parentGfx,
          element
        )
        return webhookCircle

      case "batch":
        const batchTask = this.bpmnRenderer.handlers["bpmn:ServiceTask"](
          parentGfx,
          element
        )

        const batchPathData = this.pathMap.getScaledPath("PIPES_REPEATER", {
          abspos: { x: 0, y: 0 },
        })

        this.drawPath(parentGfx, batchPathData, {
          strokeWidth: 0.1,
          fill: "black",
          stroke: "black",
        })

        return batchTask

      case "debug":
        const debugTask = this.bpmnRenderer.handlers["bpmn:Task"](
          parentGfx,
          element
        )

        const debugTaskPathData1 = this.pathMap.getScaledPath("PIPES_DEBUG_1", {
          abspos: { x: 0, y: 0 },
        })

        this.drawPath(parentGfx, debugTaskPathData1, {
          strokeWidth: 0.1,
          fill: "black",
          stroke: "black",
        })

        const debugTaskPathData2 = this.pathMap.getScaledPath("PIPES_DEBUG_2", {
          abspos: { x: 0, y: 0 },
        })

        this.drawPath(parentGfx, debugTaskPathData2, {
          strokeWidth: 0.1,
          fill: "black",
          stroke: "black",
        })

        const debugTaskPathData3 = this.pathMap.getScaledPath("PIPES_DEBUG_3", {
          abspos: { x: 0, y: 0 },
        })

        this.drawPath(parentGfx, debugTaskPathData3, {
          strokeWidth: 0.1,
          fill: "black",
          stroke: "black",
        })

        return debugTask

      case "connector":
        return this.bpmnRenderer.handlers["bpmn:ServiceTask"](
          parentGfx,
          element
        )

      case "custom":
        return this.bpmnRenderer.handlers["bpmn:Task"](parentGfx, element)

      case "xml_parser":
      case "table_parser":
        return this.bpmnRenderer.handlers["bpmn:ScriptTask"](parentGfx, element)

      case "user":
        return this.bpmnRenderer.handlers["bpmn:UserTask"](parentGfx, element)
    }
  }

  this.drawPath = (parentGfx, d, attrs) => {
    attrs = this.computeStyle(attrs, ["no-fill"], {
      strokeWidth: 2,
      stroke: "black",
    })

    const path = create("path")
    attr(path, { d })
    attr(path, attrs)
    append(parentGfx, path)

    return path
  }

  function initPathMaps(pathMaps) {
    pathMaps.PIPES_SPLITTER = {
      d: "m23.111568,18.38351c-1.303697,-2.03687 -3.540125,-2.933318 -4.995211,-2.002463c-0.12574,0.080699 -0.240846,0.172033 -0.347194,0.273376l-1.553301,-2.429105l3.586418,-5.629544c0.293394,-0.542373 0.401619,-1.187966 0.259613,-1.837312c-0.136375,-0.62057 -0.47919,-1.142299 -0.940238,-1.509511l-0.240221,-0.15264l-4.251403,6.64923l-4.251403,-6.64923l-0.240221,0.15264c-0.461048,0.367212 -0.803863,0.888941 -0.940238,1.509511c-0.142631,0.649346 -0.034407,1.294939 0.259613,1.837312l3.586418,5.629544l-1.553301,2.429105c-0.105722,-0.101343 -0.221453,-0.192677 -0.346568,-0.273376c-1.455086,-0.930855 -3.691514,-0.034407 -4.995211,2.002463s-1.180459,4.443455 0.274002,5.374309c1.455086,0.930855 3.691514,0.034407 4.995211,-2.002463l3.211699,-5.040878l3.211699,5.040878c1.303697,2.03687 3.540125,2.933318 4.995211,2.002463s1.577699,-3.337439 0.274002,-5.374309l0.000626,0zm-13.273436,2.360292c-0.586789,0.917092 -1.33998,1.363127 -1.848572,1.475104c0,0 0,0 0,0c-0.165777,0.036283 -0.396614,0.058804 -0.553633,-0.041913c-0.17391,-0.111352 -0.289641,-0.405372 -0.309034,-0.787598c-0.031279,-0.618693 0.188298,-1.34561 0.603679,-1.994957c0.586789,-0.917092 1.33998,-1.363127 1.848572,-1.475104c0.166403,-0.036283 0.39724,-0.058804 0.554259,0.041288c0.17391,0.111352 0.289641,0.405372 0.309034,0.787598c0.031904,0.618693 -0.188298,1.34561 -0.603679,1.994957l-0.000626,0.000626zm4.792525,-5.638302c-0.345317,0 -0.625574,-0.280257 -0.625574,-0.625574s0.280257,-0.625574 0.625574,-0.625574s0.625574,0.280257 0.625574,0.625574s-0.280257,0.625574 -0.625574,0.625574zm7.503764,6.28452c-0.019393,0.3816 -0.135124,0.676246 -0.309034,0.787598c-0.157019,0.100717 -0.387856,0.078197 -0.553633,0.041913c0,0 0,0 0,0c-0.509218,-0.111978 -1.261783,-0.558012 -1.848572,-1.475104c-0.415381,-0.648721 -0.635584,-1.376264 -0.603679,-1.994957c0.019393,-0.3816 0.135124,-0.676246 0.309034,-0.787598c0.157019,-0.100092 0.387856,-0.078197 0.554259,-0.041288c0.509218,0.111978 1.261783,0.558012 1.848572,1.475104c0.415381,0.648721 0.635584,1.376264 0.603679,1.994957l-0.000626,-0.000626z",
    }

    pathMaps.PIPES_BATCH = {
      d: "m17.678,5.1225c-4.032,-0.032 -7.904,2.752 -9.056,6.624c-0.896,2.784 -0.384,5.984 1.344,8.32c-1.408,-0.256 -2.848,-0.544 -4.288,-0.832c-0.128,0.64 -0.256,1.28 -0.384,1.888c2.592,0.512 5.184,1.024 7.744,1.504c0.512,-2.56 1.024,-5.12 1.504,-7.68c-0.608,-0.128 -1.248,-0.256 -1.888,-0.352c-0.288,1.536 -0.608,3.104 -0.896,4.64c-2.208,-2.624 -2.176,-6.848 0.064,-9.44c2.208,-2.752 6.4,-3.552 9.44,-1.824c2.944,1.504 4.576,5.088 3.776,8.288c-0.704,3.328 -3.968,5.888 -7.36,5.824c-0.896,-0.064 -1.344,1.216 -0.608,1.696c0.64,0.416 1.44,0.128 2.144,0.096c3.968,-0.608 7.328,-4 7.84,-8c0.608,-3.808 -1.472,-7.808 -4.864,-9.6c-1.376,-0.736 -2.944,-1.152 -4.512,-1.152z",
    }

    pathMaps.PIPES_REPEATER = {
      d: "m42.830498,3.510231c-3.922809,-0.031133 -7.689951,2.677473 -8.810754,6.444615c-0.871735,2.708606 -0.373601,5.821947 1.307603,8.094686c-1.36987,-0.249067 -2.770873,-0.529268 -4.171876,-0.809469c-0.124534,0.622668 -0.249067,1.245336 -0.373601,1.836871c2.521806,0.498135 5.043612,0.996269 7.534284,1.46327c0.498135,-2.490673 0.996269,-4.981345 1.46327,-7.472018c-0.591535,-0.124534 -1.214203,-0.249067 -1.836871,-0.342467c-0.280201,1.494404 -0.591535,3.01994 -0.871735,4.514344c-2.148205,-2.552939 -2.117072,-6.662549 0.062267,-9.184355c2.148205,-2.677473 6.226681,-3.455808 9.184355,-1.774604c2.864273,1.46327 4.452077,4.950212 3.673742,8.063552c-0.684935,3.237874 -3.860542,5.728547 -7.160684,5.66628c-0.871735,-0.062267 -1.307603,1.183069 -0.591535,1.650071c0.622668,0.404734 1.401003,0.124534 2.085938,0.0934c3.860542,-0.591535 7.12955,-3.891676 7.627685,-7.783352c0.591535,-3.704875 -1.432137,-7.596551 -4.732278,-9.340022c-1.338736,-0.716068 -2.864273,-1.120803 -4.38981,-1.120803z",
    }

    pathMaps.PIPES_RESEQUENCER_1 = {
      d: "m34.437693,9.15907l-3.679123,-3.008888c-0.459374,-0.37605 -0.835181,-0.191426 -0.835181,0.410303l0.000486,0l0,2.309746l-2.429508,0c-2.940626,0 -4.571392,1.253744 -5.66359,2.345942l-8.509474,8.509717c-0.922878,0.922878 -2.081152,1.776522 -4.289354,1.776522l-4.372435,0c-0.536625,0 -0.971706,0.435081 -0.971706,0.971706s0.435081,0.971706 0.971706,0.971706l4.372678,0c2.94014,0 4.570906,-1.253501 5.663347,-2.345699l8.509231,-8.509474c0.922878,-0.922878 2.081152,-1.776765 4.289597,-1.776765l2.429508,0l0,2.309746c0,0.601729 0.375564,0.786353 0.835181,0.410789l3.678394,-3.008645c0.460103,-0.375807 0.460103,-0.990897 0.000243,-1.366705z",
    }
    pathMaps.PIPES_RESEQUENCER_2 = {
      d: "m21.830777,21.099638c1.092198,1.092441 2.723207,2.346185 5.66359,2.346185l2.429508,0l0,2.310231c0,0.601243 0.375322,0.786353 0.835181,0.410303l3.678394,-3.008645c0.460103,-0.375807 0.460103,-0.990654 0.000243,-1.366462l-3.679123,-3.009131c-0.459374,-0.37605 -0.835181,-0.191183 -0.835181,0.410303l0.000486,0l0,2.309746l-2.429508,0c-2.208202,0 -3.366476,-0.85413 -4.289597,-1.776765l-2.193141,-2.193141l-1.373993,1.373993l2.193141,2.193384z",
    }
    pathMaps.PIPES_RESEQUENCER_3 = {
      d: "m14.695539,11.215929c-1.092198,-1.091955 -2.722964,-2.345699 -5.663347,-2.345699l-4.372678,0c-0.536625,0 -0.971706,0.435081 -0.971706,0.971706s0.435081,0.971706 0.971706,0.971706l4.372678,0c2.208202,0 3.366476,0.853887 4.289111,1.776522l2.193627,2.193384l1.374235,-1.374235l-2.193627,-2.193384z",
    }

    pathMaps.PIPES_DEBUG_1 = {
      d: "m14.693651,17.131265l2.950623,2.950968l1.567573,2.101134l0.83259,0.224875l2.312213,-2.326009l-0.22384,-0.851214l-2.086303,-1.553087l-3.111347,-3.111347l-2.202534,2.202189c-0.003104,0.123819 -0.0169,0.24419 -0.038974,0.36249zm5.127635,1.731055c0.540804,0 0.979517,0.438024 0.979517,0.978827c0,0.540114 -0.438368,0.978138 -0.979517,0.978138c-0.539769,0 -0.978827,-0.438024 -0.978827,-0.978138c0,-0.541149 0.438713,-0.978827 0.978827,-0.978827z",
    }
    pathMaps.PIPES_DEBUG_2 = {
      d: "m13.426831,11.058603l-1.727951,-1.728986c0.222116,-0.434919 0.355247,-0.872943 0.354903,-1.274407c0,-2.219089 -2.121483,-4.36575 -4.339537,-4.339537c-0.008278,0 -0.25902,0.254537 -0.395256,0.390772c1.777272,1.777617 1.630689,1.488935 1.630689,2.57951c0,0.884325 -1.417196,2.281516 -2.281861,2.281516c-1.119547,0 -0.76292,0.185556 -2.578476,-1.631034c-0.141409,0.141064 -0.391117,0.387668 -0.391462,0.395946c0.026902,2.218745 2.121483,4.339882 4.339882,4.339882c0.401464,0 0.83121,-0.128648 1.255093,-0.341451l1.570677,1.570677c0.117956,-0.021729 0.237981,-0.036904 0.360421,-0.040008l2.202879,-2.202879z",
    }
    pathMaps.PIPES_DEBUG_3 = {
      d: "m20.499705,14.840091l1.183353,1.151623c0,0 1.704153,-4.723067 -0.319378,-6.746943c-0.730844,-0.731189 -1.812107,-1.811762 -2.661252,-2.661252c-0.183832,-0.183832 -0.699113,-0.017935 -0.832245,-0.194179c-0.372148,-0.494242 -0.509073,-0.916745 -0.53839,-1.019181c-0.081397,-0.389738 -0.495277,-0.770508 -0.82983,-1.105751l-0.124509,-0.124509c-0.27868,-0.27868 -0.423193,-0.447336 -0.592884,-0.447336c-0.141409,0 -0.300753,0.117956 -0.571155,0.388358l-1.619652,1.619652c-0.594263,0.593919 -0.452854,0.652552 0.058633,1.163694l0.124854,0.124854c0.372492,0.373182 0.802238,0.844661 1.237848,0.844661c0.005174,0 0.010347,0 0.015865,0c0.334898,0.062427 1.026424,0.258675 1.344422,0.842937c0.349384,0.774302 -0.144858,1.306483 -0.342831,1.603787l-4.341262,4.341262c-0.13934,-0.044147 -0.2773,-0.06829 -0.409742,-0.06829c-0.235222,0 -0.452509,0.075878 -0.629443,0.253157c-0.032766,0.032766 -0.080362,0.079672 -0.138305,0.13796c0,0 -3.575583,3.575238 -4.503365,4.502675c-0.058288,0.058978 -0.105884,0.10554 -0.13865,0.138995c-0.882255,0.882945 0.728775,2.788865 1.90454,2.788865c0.235567,0 0.453889,-0.076913 0.631168,-0.253847c0.033455,-0.03311 0.080707,-0.079672 0.13865,-0.13796c0.930197,-0.930541 4.50302,-4.50371 4.50302,-4.50371c0.057943,-0.057943 0.10554,-0.10485 0.138305,-0.137615c0.276265,-0.276265 0.306961,-0.652897 0.184522,-1.039875l4.865511,-4.8662c0.303167,-0.253847 0.6053,-0.488034 0.777406,-0.488034c0.025868,0 0.048631,0.005174 0.067945,0.016555c1.761407,1.010558 1.416851,3.875646 1.416851,3.875646z",
    }
  }
}

inherits(CustomRenderer, BaseRenderer)

CustomRenderer.$inject = [
  "config",
  "eventBus",
  "styles",
  "pathMap",
  "canvas",
  "textRenderer",
]
