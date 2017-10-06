import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {Field, reduxForm} from 'redux-form'

import {isInteger} from "utils/validations";
import * as topologyActions from 'actions/topologyActions';

import {FormTextInput, FormCheckboxInput, FormNumberInput} from 'elements/formInputs';

class TopologyForm extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.setButton = this.setButton.bind(this);
    this._button = null;
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
    this.props.commitAction({name, descr, enabled: Boolean(enabled)}).then(
      response => {
        const {onSuccess} = this.props;
        if (response){
          if (onSuccess){
            onSuccess(this);
          }
        }
        return response;
      }
    )
  }

  render() {
    const {addNew, initialValues} = this.props;

    let enabledReadonly = false;
    let enabledDescripton = "";

    if (addNew || !initialValues.status || initialValues.status !== 'public') {
        enabledReadonly = true;
        enabledDescripton = "Please publish topology first";
    }

    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        {!addNew && <Field name="_id" component={FormTextInput} label="Id" readOnly/>}
        <Field name="name" component={FormTextInput} label="Name" />
        <Field name="descr" component={FormTextInput} label="Description" />
        <Field name="enabled" component={FormCheckboxInput} label="Enabled" description={enabledDescripton} readOnly={enabledReadonly} />
        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

TopologyForm.propTypes = {
  addNew: PropTypes.bool,
  handleSubmit: PropTypes.func.isRequired,
  setSubmit: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  commitAction: PropTypes.func.isRequired
};

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
    initialValues: ownProps.addNew ? {enabled: false} : topology.elements[ownProps.topologyId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    commitAction: (data) => dispatch(
      ownProps.addNew ? topologyActions.topologyCreate(data, ownProps.newProcessId) : topologyActions.topologyUpdate(ownProps.topologyId, data)
    )
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({validate})(TopologyForm));