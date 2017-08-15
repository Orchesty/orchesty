import React from 'react'
import Flusanec from 'flusanec';
import DATA_STATE from 'flusanec/src/core/data_state';
import SimpleState from '../../components/simple_state/simple_state';
import BpmnIoComponent from '../../../common/bpmn/bpmn_io_component';

class SchemaEditor extends Flusanec.Component {
  _initialize() {
    this._state = DATA_STATE.NOT_LOADED;
  }

  _useProps(props) {
    if (this._state == DATA_STATE.NOT_LOADED && props.promise) {
      this.setPromise(props.promise);
    }
  }

  _finalization() {
    this.scheme = null;

  }

  setPromise(promise) {
    this._state = DATA_STATE.LOADING;
    promise.then(file => {
      this._state = file ? DATA_STATE.SUCCESS : DATA_STATE.ERROR;
      this.scheme = file;
      this.forceUpdate();
    });
  }

  set scheme(value){
    if (this._scheme != value){
      this._scheme = value;
    }
  }

  render() {
    return (
      <SimpleState state={this._state}>
        <BpmnIoComponent scheme={this._scheme} contextServices={this.props.contextServices} />
      </SimpleState>
    );
  }
}

export default SchemaEditor;