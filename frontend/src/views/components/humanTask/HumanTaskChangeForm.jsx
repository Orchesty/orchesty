import React from 'react'
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form'

import { isJSON } from 'rootApp/utils/validations';

import { FormTextAreaInput } from 'elements/formInputs';
import * as humanTasksActions from 'rootApp/actions/humanTaskActions';

class HumanTaskChangeForm extends React.Component {
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
    const { id, onInnerChange, onSuccess } = this.props;

    onInnerChange(id, body ? JSON.parse(body) : {}).then(
      response => {
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

HumanTaskChangeForm.propTypes = {
  onInnerChange: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  handleSubmit: PropTypes.func.isRequired
};

function mapStateToProps(state, { data: body }) {
  return {
    initialValues: { body }
  };
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    onInnerChange: (id, body) => dispatch(humanTasksActions.humanTaskChange(ownProps.componentKey, id, body)),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({
  validate,
  initialValues: { body: "{}" }
})(HumanTaskChangeForm));
