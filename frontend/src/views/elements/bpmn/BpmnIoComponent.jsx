import React from 'react'
import PropTypes from 'prop-types';
import ReactDOM from 'react-dom';

import 'diagram-js/assets/diagram-js.css';
import 'bpmn-js/assets/bpmn-font/css/bpmn-embedded.css';
import './BpmnIoComponent.less';
import './custom-modeler/assets/elements.css';

import emptySchema from './empty-schema.bpmn';

import CustomBPMNModeler from './custom-modeler';
import download from '../../../utils/download';

class BpmnIoComponent extends React.Component {
  constructor(props){
    super(props);
    this._self = null;
    this._modeler = null;
    this._changed = false;
  }

  _sendActions(){
    this.props.setActions([
      {
        caption: 'Save',
        action: this.saveBPMN.bind(this),
        disabled: !this._changed
      },
      {
        caption: 'Import / Export',
        items: [
          {
            caption: 'Import BPMN',
            action: this.importBPMN.bind(this)
          },
          {
            caption: 'Export BPMN',
            action: this.exportBPMN.bind(this)
          },
          {
            caption: 'Export SVG',
            action: this.exportSVG.bind(this)
          }
        ]
      }
    ])
  }

  componentWillMount(){
    this._sendActions();
  }

  componentDidMount() {
    const parent = ReactDOM.findDOMNode(this);
    this._modeler = new CustomBPMNModeler({
      propertiesPanel: {
        parent: parent.childNodes[1]
      },
    });
    this._modeler.attachTo(parent.childNodes[0]);
    this.loadXML();
    this._modeler.get('eventBus').on('commandStack.changed', e => {this.changed()});

    parent.childNodes[2].addEventListener('change', e => {
      const reader = new FileReader();
      const file = e.target.files[0];
      reader.onload = response => {
        this.openBPMN({file, content: response.target.result});
      };
      reader.readAsText(file);
    })
  }

  componentWillUnmount() {
    this.props.setActions(null);
    this._modeler.detach();
  }

  changed(){
    if (!this._changed){
      this._changed = true;
      this._sendActions();
    }
  }

  loadXML() {
    const schema = this.props.schema ? this.props.schema : emptySchema;
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
          download(xml, 'export.bpmn', 'application/bpmn+xml');
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
          download(svg, 'export.svg', 'image/svg+xml');
        }
      });
    }
  }

  saveBPMN(){
    if (this._modeler){
      this._modeler.saveXML((err, xml) => {
        if (err){
          err && this.props.onError(STring(err));
        }
        else if (this.props.onSave){
          this.props.onSave(xml);
        }
      });
    }
  }

  importBPMN(){
    const parent = ReactDOM.findDOMNode(this);
    parent.childNodes[2].click();
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

  setSelf(self) {
    this._self = self;
   // this.calculateHeight();
  }

  render() {
    return (
      <div ref={self => {this.setSelf(self)}} className="bpmn-io-component">
        <div className="bpmn-io-canvas"/>
        <div className="bpmn-io-properties"/>
        <input className="open-file-dialog" type="file" />
      </div>
    );
  }
}

BpmnIoComponent.propTypes = {
  onSave: PropTypes.func,
  schema: PropTypes.string,
  onError: PropTypes.func.isRequired,
  onImport: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired
};

export default BpmnIoComponent;