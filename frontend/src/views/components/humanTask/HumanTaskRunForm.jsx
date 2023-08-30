import React from 'react'
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form'

import { isJSON } from 'rootApp/utils/validations';

import { FormTextAreaInput } from 'elements/formInputs';
import * as humanTasksActions from 'rootApp/actions/humanTaskActions';

class HumanTaskRunForm extends React.Component {
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
    const { body } = data;
    const { topology, node, token, approve } = this.props;

    this.props.process(topology, node, token, approve, body ? JSON.parse(body) : {}).then(
      response => {
        const { onSuccess } = this.props;
        if (response) {
          if (onSuccess) {
            onSuccess(this);
          }
        }
        return response;
      }
    )
  }

  render() {
    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="body" component={FormTextAreaInput} label="JSON Body" rows={12} />
        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

function validate(values) {
  const errors = {};
  if (values.body && !isJSON(values.body)) {
    errors.body = 'Body is not valid JSON.';
  }

  return errors;
}

HumanTaskRunForm.propTypes = {
  process: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  handleSubmit: PropTypes.func.isRequired
};

function mapStateToProps(state, ownProps) {
  return {};
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    process: (topology, node, token, approve, body) => dispatch(humanTasksActions.humanTaskProcess(ownProps.componentKey, topology, node, token, approve, body)),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({
  validate,
  initialValues: { body: "{}" }
})(HumanTaskRunForm));