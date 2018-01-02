import React from 'react'
import PropTypes from 'prop-types';
import {Field, reduxForm} from 'redux-form';
import StateButton from 'rootApp/views/elements/input/StateButton';
import {FormTagsInput} from 'rootApp/views/elements/formInputs';

class NotificationSettingsForm extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
  }


  onSubmit(data){
    console.log(data);
  }

  render() {
    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="emails" component={FormTagsInput} label="emails" onlyUnique/>
        <StateButton type="submit" color="primary">Save</StateButton>
      </form>
    );
  }
}

function validate(values){
  const errors = {};

  return errors;
}

NotificationSettingsForm.propTypes = {};

export default reduxForm({validate, form: 'notification-settings', initialValues: {emails: []}})(NotificationSettingsForm);