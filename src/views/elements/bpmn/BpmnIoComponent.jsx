import React from 'react'
import PropTypes from 'prop-types';
import ReactDOM from 'react-dom';

import 'diagram-js/assets/diagram-js.css';
import 'bpmn-js/assets/bpmn-font/css/bpmn-embedded.css';
import './BpmnIoComponent.less';
import './custom-modeler/assets/elements.css';

import emptySchema from './empty-schema.bpmn';

import CustomBPMNModeler from './custom-modeler';
import download from 'utils/download';
import {menuItemType} from 'rootApp/types';
import SidebarNodeMetrics from 'rootApp/views/components/metrics/SidebarNodeMetrics';
import SidebarTopologyMetrics from 'rootApp/views/components/metrics/SidebarTopologyMetrics';

class BpmnIoComponent extends React.Component {
  constructor(props){
    super(props);
    this._modeler = null;
    this._changed = false;
    this._fileInputElement = null;
    this._canvasBpmnElement = null;
    this._propertiesElement = null;
    this.setFileInput = this.setFileInput.bind(this);
    this.setCanvasElement = this.setCanvasElement.bind(this);
    this.setPropertiesElement = this.setPropertiesElement.bind(this);
    this.selectionChanged = this.selectionChanged.bind(this);
    this.propPanelToggle = this.propPanelToggle.bind(this);
    this.changed = this.changed.bind(this);
    this.state = {
      selectedId: null
    }
  }

  _sendActions(){
    this.props.setActions([
      {
        caption: 'Save',
        type: menuItemType.ACTION,
        action: this.saveBPMN.bind(this),
        disabled: !this._changed,
        processId: this.props.saveProcessId
      },
      {
        caption: 'Import / Export',
        type: menuItemType.SUB_MENU,
        items: [
          {
            type: menuItemType.ACTION,
            caption: 'Import BPMN',
            action: this.importBPMN.bind(this)
          },
          {
            type: menuItemType.ACTION,
            caption: 'Export BPMN',
            action: this.exportBPMN.bind(this)
          },
          {
            type: menuItemType.ACTION,
            caption: 'Export SVG',
            action: this.exportSVG.bind(this)
          }
        ]
      }
    ]);
    this.props.setPanelActions([
      {
        type: menuItemType.ACTION,
        caption: 'Toggle info box',
        icon: 'fa fa-info dark',
        action: () => this.props.onPropPanelToggle()
      },
    ]);
  }

  componentWillMount(){
    this._sendActions();
  }

  componentDidMount() {
    this.createBpmn();
  }

  componentWillUnmount(){
    this.props.setActions(null);
    this._modeler.detach();
  }

  componentDidUpdate(prevProps){
    if (prevProps.schema !== this.props.schema){
      this.loadXML();
    }
  }

  createBpmn(){
    this._modeler = new CustomBPMNModeler({
      propertiesPanel: {
        parent: this._propertiesElement
      },
    });
    this._modeler.attachTo(this._canvasBpmnElement);
    this.loadXML();
    this._modeler.get('eventBus').on('commandStack.changed', this.changed);
    this._modeler.get('eventBus').on('selection.changed', this.selectionChanged);
  }

  selectionChanged(event){
    const {selectedId} = this.state;
    if (event.newSelection.length > 0) {
      if (selectedId != event.newSelection[0].id){
        this.setState({selectedId: event.newSelection[0].id});
      }
    } else if (selectedId) {
      this.setState({selectedId: null});
    }
  }

  setFileInput(element){
    if (element !== this._fileInputElement) {
      this._fileInputElement = element;
      if (this._fileInputElement){
        this._fileInputElement.addEventListener('change', e => {
          const reader = new FileReader();
          const file = e.target.files[0];
          reader.onload = response => {
            this.openBPMN({file, content: response.target.result});
          };
          reader.readAsText(file);
        });
      }
    }
  }

  setCanvasElement(element){
    this._canvasBpmnElement = element;
  }

  setPropertiesElement(element){
    this._propertiesElement = element;
  }

  changed(){
    if (!this._changed){
      this._changed = true;
      this._sendActions();
    }
  }

  propPanelToggle(e){
    const {onPropPanelToggle} = this.props;
    e.preventDefault();
    onPropPanelToggle();
  }

  loadXML() {
    const schema = (this.props.schema && this.props.schema !== '') ? this.props.schema : emptySchema;
    this._modeler.importXML(schema, err => {
      err && this.props.onError(String(err));
    });
  }

  exportBPMN(){
    if (this._modeler){
      this._modeler.saveXML({format: true}, (err, xml) => {
        if (err){
          err && this.props.onError(String(err));
        }
        else{
          const {topologyName} = this.props;
          download(xml, (topologyName ? topologyName : 'export') + '.tplg', 'application/bpmn+xml');
        }
      })
    }
  }

  exportSVG(){
    if (this._modeler){
      this._modeler.saveSVG((err, svg) => {
        if (err){
          err && this.props.onError(String(err));
        }
        else {
          const {topologyName} = this.props;
          download(svg, (topologyName ? topologyName : 'export') + '.svg', 'image/svg+xml');
        }
      });
    }
  }

  saveBPMN(){
    if (this._modeler){
      this._modeler.saveXML((err, xml) => {
        if (err){
          err && this.props.onError(String(err));
        }
        else if (this.props.onSave){
          this.props.onSave(xml);
        }
      });
    }
  }

  importBPMN(){
    if (this._fileInputElement){
      this._fileInputElement.click();
    }
  }

  openBPMN(data){
    this._modeler.importXML(data.content, err => {
      if (err) {
        err && this.props.onError(String(err));
      }
      else {
        this.props.onImport(`File [${data.file.name}] imported.`);
      }
    });
  }

  setSelf(self) {
    this._self = self;
  }

  render() {
    const {topologyId, metricsRange, showEditorPropPanel} = this.props;
    const {selectedId} = this.state;
    return (
      <div ref={self => {this.setSelf(self)}} className="bpmn-io-component">
        <div ref={this.setCanvasElement} className="bpmn-io-canvas"/>
        <div className={'node-box' + (showEditorPropPanel ? '' : ' hidden')}>
          <a href="#" className="close-link" onClick={this.propPanelToggle}><i className="fa fa-times" /></a>
          <div ref={this.setPropertiesElement} className="bpmn-io-properties"/>
          {topologyId && selectedId && <SidebarNodeMetrics topologyId={topologyId} schemaId={selectedId} metricsRange={metricsRange} />}
          {topologyId && !selectedId && <SidebarTopologyMetrics topologyId={topologyId} metricsRange={metricsRange} />}
        </div>
        <input ref={this.setFileInput} className="open-file-dialog" type="file" />
      </div>
    );
  }
}

BpmnIoComponent.propTypes = {
  onSave: PropTypes.func,
  schema: PropTypes.string,
  topologyName: PropTypes.string,
  onError: PropTypes.func.isRequired,
  onImport: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired,
  setPanelActions: PropTypes.func.isRequired,
  saveProcessId: PropTypes.string,
  topologyId: PropTypes.string,
  metricsRange: PropTypes.object,
  onPropPanelToggle: PropTypes.func.isRequired,
  showEditorPropPanel: PropTypes.bool.isRequired
};

export default BpmnIoComponent;