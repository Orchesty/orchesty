import React from 'react'
import {connect} from 'react-redux';
import {Field, reduxForm} from 'redux-form'

import * as topologyActions from '../../../actions/topologyActions';

import {FormTextInput, FormCheckboxInput} from '../../elements/formInputs';

class TopologyForm extends React.Component {
  constructor(props) {
    super(props);
    this._onSubmit = this.onSubmit.bind(this);
    this._setButton = this.setButton.bind(this);
  }

  componentDidMount() {
    this.props.setSubmit(this.submit.bind(this));
  }

  setButton(button){
    this._button = button;
  }

  submit(){
    this._button.click();
  }

  onSubmit(data){
    const {name, descr, enabled} = data;
    const {onProcessing} = this.props;
    if (typeof onProcessing == 'function'){
      onProcessing(true);
    }
    this.props.topologyUpdate({name, descr, enabled: Boolean(enabled)}).then(
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
    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this._onSubmit)}>
        <Field name="_id" component={FormTextInput} label="Id" readOnly/>
        <Field name="name" component={FormTextInput} label="Name" />
        <Field name="descr" component={FormTextInput} label="Description" />
        <Field name="enabled" component={FormCheckboxInput} label="Enabled" />
        <button ref={this._setButton} className="hidden" />
      </form>
    );
  }
}

function validate(values){
  const errors = {};
  if (!values.name) {
    errors.name = 'Name is required';
  }

  return errors;
}

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    initialValues: topology.elements[ownProps.topologyId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    topologyUpdate: (data) => dispatch(topologyActions.topologyUpdate(ownProps.topologyId, data))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({validate})(TopologyForm));