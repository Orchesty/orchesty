import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {Field, reduxForm} from 'redux-form'

import * as topologyActions from 'actions/topologyActions';
import * as applicationActions from 'actions/applicationActions';

import {FormTextInput} from 'elements/formInputs';

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

  setButton(button) {
    this._button = button;
  }

  submit() {
    this._button.click();
  }

  onSubmit(data) {
    const {addNew, initialValues} = this.props;
    const {name, descr, enabled} = data;
    const sendData = {descr, enabled: Boolean(enabled)};
    if (addNew || initialValues.visibility !== 'public') {
      sendData['name'] = name;
    }
    this.props.commitAction(sendData).then(
      response => {
        const {onSuccess, openTopology, isNew} = this.props;

        if (response) {
          if (onSuccess) {
            onSuccess(this);
          }

          if (isNew) {
            openTopology(response._id);
          }
        }
        return response;
      }
    )
  }

  render() {
    const {addNew, initialValues} = this.props;
    const nameReadOnly = !(addNew || initialValues.visibility !== 'public');

    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        {!addNew && <Field name="_id" component={FormTextInput} label="Id" readOnly/>}
        <Field name="name" component={FormTextInput} label="Name" readOnly={nameReadOnly} autoFocus/>
        <Field name="descr" component={FormTextInput} label="Description"/>
        <button ref={this.setButton} className="hidden"/>
      </form>
    );
  }
}

TopologyForm.propTypes = {
  addNew: PropTypes.bool,
  handleSubmit: PropTypes.func.isRequired,
  setSubmit: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  openTopology: PropTypes.func.isRequired,
  isNew: PropTypes.bool.isRequired,
  commitAction: PropTypes.func.isRequired
};

function validate(values) {
  const errors = {};
  if (!values.name) {
    errors.name = 'Name is required';
  }

  return errors;
}

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    initialValues: ownProps.addNew ? {enabled: false} : topology.elements[ownProps.topologyId],
    isNew: ownProps.addNew,
  };
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    commitAction: (data) => dispatch(
      ownProps.addNew ? topologyActions.topologyCreate(Object.assign(data, {category: ownProps.categoryId ? ownProps.categoryId : null}), ownProps.newProcessId) : topologyActions.topologyUpdate(ownProps.topologyId, data)
    ),
    openTopology: id => dispatch(applicationActions.openPage('topology_detail', {topologyId: id, activeTab: 'schema'}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({validate})(TopologyForm));