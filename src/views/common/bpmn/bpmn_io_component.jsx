import React from 'react'
import ReactDOM from 'react-dom';
import Flusanec from 'flusanec';
import FlusanecMenu from 'flusanec/src/view_models/menu';
import Notification from '../../../services/notification/notification';

import BpmnModeler from 'bpmn-js/lib/Modeler';
import PropertiesPanelModule from 'bpmn-js-properties-panel';
import PropertiesProviderModule from 'bpmn-js-properties-panel/lib/provider/camunda';
import CamundaModdleDescriptor from 'camunda-bpmn-moddle/resources/camunda';

let diagram_css = require('diagram-js/assets/diagram-js.css');
let bpmn_embedded_css = require('bpmn-js/assets/bpmn-font/css/bpmn-embedded.css');
let bpmn_less = require('./bpmn_io_component.less');

class BpmnIoComponent extends Flusanec.Component {
  _initialize() {
    this._onWindowResize = this.onWindowResize.bind(this);
    this._scheme = null;
    this.state = {
      height: 300
    };
    this._menuItems = [
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Import BPMN', null, () => {this.importBPMN()}),
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Export BPMN', null, () => {this.exportBPMN()}),
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Export SVG', null, () => {this.exportSVG()}),
    ]
  }

  _useProps(props) {
    this.scheme = props.scheme;
  }

  _finalization() {
  }

  set scheme(value) {
    if (this._scheme != value) {
      this._scheme = value;
      this._modeler && this.loadXML();
    }
  }

  loadXML() {
    this._modeler.importXML(this._scheme, err => {
      err && this.props.contextServices.notifyService.createNotification(Notification.ERROR, String(err));
    });
  }

  exportBPMN(){
    if (this._modeler){
      this._modeler.saveXML({format: true}, function (err, xml) {
        if (err){
          this.props.contextServices.notifyService.createNotification(Notification.ERROR, String(err));
        }
        else{
          Flusanec.download(xml, 'export.bpmn', 'application/bpmn-20-xml');
        }
      })
    }
  }

  exportSVG(){
    if (this._modeler){
      this._modeler.saveSVG(function (err, svg) {
        if (err){
          this.props.contextServices.notifyService.createNotification(Notification.ERROR, String(err));
        }
        else {
          Flusanec.download(svg, 'export.svg', 'image/svg+xml');
        }
      });
    }
  }

  importBPMN(){
    this.props.contextServices.openFileDialogService.openFile().then(response => {
      this._modeler.importXML(response.content, err => {
        if (err) {
          this.props.contextServices.notifyService.createNotification(Notification.ERROR, String(err));
        }
        else {
          this.props.contextServices.notifyService.createNotification(Notification.SUCCESS, 'BPMN file was successfully opened.');
        }
      });
    });
  }

  onWindowResize() {
    this.calculateHeight();
  }

  calculateHeight() {
    if (this._self){
      this.setState({
        height: window.innerHeight - this._self.getBoundingClientRect().top - window.scrollX - 10
      });
    }
  }

  componentDidMount() {
    if (this.props.contextServices && this.props.contextServices.menu){
      this.props.contextServices.menu.addMenuItems(this._menuItems);
    }
    window.addEventListener('resize', this._onWindowResize);
    this.calculateHeight();
    const parent = ReactDOM.findDOMNode(this);
    this._modeler = new BpmnModeler({
      propertiesPanel: {
        parent: parent.childNodes[1]
      },
      additionalModules: [
        PropertiesPanelModule,
        PropertiesProviderModule
      ],
      moddleExtensions: {
        camunda: CamundaModdleDescriptor
      }
    });
    this._modeler.attachTo(parent.childNodes[0]);
    this._scheme && this.loadXML();
  }

  componentWillUnmount() {
    super.componentWillUnmount();
    if (this.props.contextServices && this.props.contextServices.menu){
      this.props.contextServices.menu.removeMenuItems(this._menuItems);
    }
    window.removeEventListener('resize', this._onWindowResize);
    this._modeler.detach();
  }

  setSelf(self) {
    this._self = self;
   // this.calculateHeight();
  }

  // shouldComponentUpdate() {
  //   return false;
  // }

  render() {
    return (
      <div ref={self => {this.setSelf(self)}} className="bpmn-io-component" style={{height: this.state.height + 'px'}}>
        <div className="bpmn-io-canvas"/>
        <div className="bpmn-io-properties"/>
      </div>
    );
  }
}

export default BpmnIoComponent;