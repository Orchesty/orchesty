import React from 'react'
import {connect} from 'react-redux';
import Form from 'react-jsonschema-form';

import * as topologyActions from '../../../actions/topologyActions';


const schema = {
  type: "object",
  required: ["name"],
  properties: {
    _id: {
      type: 'string',
      title: 'Id'
    },
    name: {
      type: "string",
      title: "Name",
      default: "Nejaky nazev"
    },
    descr: {
      type: "string",
      title: "Description",
      default: "Description"
    },
    status: {
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

class TopologyForm extends React.Component {
  constructor(props) {
    super(props);
    this._submitButton = null;
  }

  componentDidMount() {
    this.props.setSubmit(this.submit.bind(this));
  }
  
  submit(){
    this._submitButton.click();
  }

  onSubmit(data){
    const {name, descr, status} = data;
    const {onProcessing} = this.props;
    if (typeof onProcessing == 'function'){
      onProcessing(true);
    }
    this.props.topologyUpdate({name, descr, status: status ? 1 : 0}).then(
      response => {
        const {onSuccess, onProcessing} = this.props;
        if (typeof onProcessing == 'function'){
          onProcessing(false);
        }
        if (response){
          if (typeof onSuccess == 'function'){
            onSuccess(this);
          }
        }
        return response;
      }      
    )
  }

  render() {
    const {topology} = this.props;
    if (topology){
      return <Form
        schema={schema}
        formData={topology}
        onSubmit={data => {this.onSubmit(data.formData)}}
        showErrorList={false}
        uiSchema={uiSchema}
        liveValidate={true}
      >
        <button ref={button => {this._submitButton = button}} className="hidden" />
      </Form>;
    }
    return (
      <span>Waiting for data</span>
    );
  }
}

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    topology: topology.elements[ownProps.topologyId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    topologyUpdate: (data) => dispatch(topologyActions.topologyUpdate(ownProps.topologyId, data))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyForm);