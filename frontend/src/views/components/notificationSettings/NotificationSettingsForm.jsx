import React from 'react'
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form'

import * as notificationSettingsActions from 'rootApp/actions/notificationSettingsActions';
import { FormSelectInput, FormTextAreaInput, FormTextInput } from '../../elements/formInputs';

class NotificationSettingsChangeForm extends React.Component {
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
    const { id, onInnerChange, onSuccess } = this.props;

    onInnerChange(id, data).then(
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
    const { events, initialValues: { type } } = this.props;

    const methods = [
      { value: 'GET', label: 'GET' },
      { value: 'POST', label: 'POST' },
      { value: 'PUT', label: 'PUT' },
      { value: 'DELETE', label: 'DELETE' },
    ];

    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="events" component={FormSelectInput} label="Events" multiple={true} options={events} />
        {type === "curl" && <Field name="method" component={FormSelectInput} label="Method" options={methods} />}
        {type === "curl" && <Field name="url" component={FormTextInput} label="Url" />}
        {type === "email" &&
        <Field name="emails" component={FormTextAreaInput} rows={10} label="Emails (one per line)" />}
        {type === "rabbit" && <Field name="queue" component={FormTextInput} label="Queue" />}
        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

function validate({ type, method, url, emails, queue }) {
  const errors = {};

  switch (type) {
    case 'curl':
      if (!method) {
        errors.method = 'Method must be filled!';
      }

      if (!url) {
        errors.url = 'URL must be filled!';
      }
      break;

    case 'email':
      if (!emails) {
        errors.emails = 'Emails must be filled!';
      }
      break;

    case 'rabbit':
      if (!queue) {
        errors.queue = 'Queue must be filled!';
      }
      break;
  }

  return errors;
}

NotificationSettingsChangeForm.propTypes = {
  onInnerChange: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  handleSubmit: PropTypes.func.isRequired
};

function mapStateToProps({ notificationSettings: { events } }, { data }) {
  const { settings: { method, url, emails, queue } } = data;

  return {
    events: Object.entries(events).map(([value, label]) => ({ value, label })),
    initialValues: { ...data, method, url, emails: emails && emails.join('\r\n'), queue }
  };
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    onInnerChange: (id, data) => dispatch(notificationSettingsActions.notificationSettingsChange(ownProps.componentKey, id, data)),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({ validate })(NotificationSettingsChangeForm));
