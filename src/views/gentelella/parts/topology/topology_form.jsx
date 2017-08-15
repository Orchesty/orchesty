import React from 'react'
import Flusanec from 'flusanec';
import DATA_STATE from 'flusanec/src/core/data_state';
import SimpleState from '../../components/simple_state/simple_state';
import Form from 'react-jsonschema-form';

const schema = {
  type: "object",
  required: ["_name"],
  properties: {
    _id: {
      type: 'integer',
      title: 'Id'
    },
    _name: {
      type: "string",
      title: "Name",
      default: "Nejaky nazev"
    },
    _description: {
      type: "string",
      title: "Description",
      default: "Description"
    },
    _enabled: {
      type: 'boolean',
      title: 'Enabled',
      default: true
    }
  }
};

const uiSchema = {
  _id: {
    "ui:readonly": true
  }
};

class TopologyForm extends Flusanec.Component {
  _initialize() {
    this._onChange = this.onChange.bind(this);
    this._onRelease = this.onRelease.bind(this);
    this._onStateChange = this.onStateChange.bind(this);
    this._onUpdateFinish = this.onUpdateFinish.bind(this);
    this._topology = null;
    this._updating = false;
  }

  _useProps(props) {
    this.topology = props.topology;
  }

  _finalization() {
    this.topology = null;
  }

  set topology(topology:Topology) {
    if (this._topology != topology) {
      this._topology && this._topology.removeChangeListener(this._onChange);
      this._topology && this._topology.removeReleaseListener(this._onRelease);
      this._topology && this._topology.removeStateListener(this._onStateChange);
      this._topology = topology;
      this._topology && this._topology.addChangeListener(this._onChange);
      this._topology && this._topology.addReleaseListener(this._onRelease);
      this._topology && this._topology.addStateListener(this._onStateChange);
    }
  }

  onChange() {
    this.forceUpdate();
  }

  onRelease() {
    this.topology = null;
    this.forceUpdate();
  }

  onStateChange() {
    this.forceUpdate();
  }

  onUpdateFinish(){
    this._updating = false;
    this.forceUpdate();
  }

  setPromise(promise:ES6Promise){
    this._updating = true;
    promise.then(this._onUpdateFinish);
  }

  submit(formData) {
    if (typeof this.props.update == 'function' && this._topology) {
      this._updating = true;
      this.setPromise(
        this.props.update(this._topology, {
          name: formData._name,
          description: formData._description,
          enabled: formData._enabled
        })
      );
      this.forceUpdate();
    }
  }

  render() {
    if (this._topology.objectState == DATA_STATE.SUCCESS) {
      return <Form schema={schema} formData={this._topology} onSubmit={data => {this.submit(data.formData)}}
        showErrorList={false} uiSchema={uiSchema}/>;
    }
    else {
      return (
        <SimpleState state={this._topology.objectState}></SimpleState>
      );
    }
  }
}

export default TopologyForm;